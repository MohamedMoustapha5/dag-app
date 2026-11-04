<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAbonnementController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Abonnements, Action::Voir), 403);

        $abonnements = Abonnement::query()
            ->with(['client', 'entreprise', 'formule', 'factures'])
            ->latest('date_souscription')
            ->get();

        $lignes = [];
        foreach ($abonnements as $abonnement) {
            $lignes[] = $this->ligne($abonnement);
        }

        $resume = ['renouvellementsDemandes' => 0, 'bientot' => 0, 'expires' => 0];
        foreach ($lignes as $ligne) {
            if ($ligne['demandeRenouvellement'] !== null) {
                $resume['renouvellementsDemandes']++;
            }
            if ($ligne['etat'] === 'bientot') {
                $resume['bientot']++;
            }
            if ($ligne['etat'] === 'expire') {
                $resume['expires']++;
            }
        }

        $formules = Formule::query()
            ->withCount('abonnements')
            ->orderByDesc('populaire')
            ->orderBy('nom')
            ->get();

        return Inertia::render('espaces/admin-abonnements', [
            ...$this->adminEspaceProps($membre),
            'peutCreer' => $membre->peut(Donnee::Abonnements, Action::Creer),
            'peutModifier' => $membre->peut(Donnee::Abonnements, Action::Modifier),
            'peutSupprimer' => $membre->peut(Donnee::Abonnements, Action::Supprimer),
            'resume' => $resume,
            'abonnements' => $lignes,
            'formules' => $formules->map(fn (Formule $formule) => [
                'id' => $formule->id,
                'nom' => $formule->nom,
                'contenu' => $formule->contenu,
                'servicesInclus' => $formule->services_inclus ?? [],
                'prixAffiche' => $formule->prix_affiche,
                'populaire' => $formule->populaire,
                'statut' => $formule->statut,
                'abonnementsCount' => $formule->abonnements_count,
            ]),
            'servicesDisponibles' => Service::query()
                ->where('statut', 'PUBLIE')
                ->orderBy('nom')
                ->pluck('nom'),
            'clients' => Client::query()->orderBy('nom')->get(['id', 'nom', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Abonnements, Action::Creer), 403);

        $data = $request->validate([
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'formule_id' => ['required', 'uuid', 'exists:formules,id'],
            'montant_negocie' => ['required', 'numeric', 'min:0'],
            'date_souscription' => ['required', 'date'],
            'cycle_debut' => ['nullable', 'date'],
            'echeance' => ['nullable', 'date', 'after_or_equal:cycle_debut'],
        ]);

        Abonnement::create([...$data, 'statut' => 'BROUILLON']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement créé.']);

        return back();
    }

    public function update(Request $request, Abonnement $abonnement): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Abonnements, Action::Modifier), 403);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['BROUILLON', 'ACTIF', 'SUSPENDU', 'TERMINE'])],
            'cycle_debut' => ['nullable', 'date'],
            'echeance' => ['nullable', 'date', 'after_or_equal:cycle_debut'],
        ]);

        $datesSaisies = $request->filled('cycle_debut') || $request->filled('echeance');
        $reactivation = $data['statut'] === 'ACTIF'
            && ! $datesSaisies
            && (
                in_array($abonnement->statut, ['BROUILLON', 'TERMINE'], true)
                || $abonnement->echeance === null
                || $abonnement->etatEcheance() === 'expire'
            );

        if ($reactivation) {
            $abonnement->renouveler();

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement activé : nouveau cycle lancé.']);

            return back();
        }

        $abonnement->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement mis à jour.']);

        return back();
    }

    /**
     * Renews a subscription: a new cycle of the same length starts (from the
     * old end date when it is still running, from today otherwise).
     */
    public function renouveler(Request $request, Abonnement $abonnement): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::Abonnements, Action::Modifier), 403);
        abort_if($abonnement->statut === 'BROUILLON', 409, "Activez d'abord l'abonnement.");

        $abonnement->renouveler();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Abonnement renouvelé : nouveau cycle lancé.']);

        return back();
    }

    public function storeFormule(Request $request): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::Abonnements, Action::Creer), 403);

        Formule::create($this->validatedFormule($request));

        return back();
    }

    public function updateFormule(Request $request, Formule $formule): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::Abonnements, Action::Modifier), 403);

        $formule->update($this->validatedFormule($request, sometimes: true));

        return back();
    }

    public function destroyFormule(Request $request, Formule $formule): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::Abonnements, Action::Supprimer), 403);

        $formule->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function ligne(Abonnement $abonnement): array
    {
        $mois = $abonnement->moisDuCycle();
        $paiementDuMois = null;
        foreach ($mois as $segment) {
            if ($segment['courant']) {
                $paiementDuMois = $segment['facture'];
            }
        }

        $demande = $abonnement->demandeRenouvellementEnCours();

        return [
            'id' => $abonnement->id,
            'client' => $this->nomSouscripteur($abonnement),
            'formule' => $abonnement->formule_id !== null ? $abonnement->formule->nom : '—',
            'montant' => $abonnement->montant_negocie,
            'echeance' => $abonnement->echeance?->toDateString(),
            'statut' => $abonnement->statut,
            'progression' => $abonnement->progressionCycle(),
            'joursRestants' => $abonnement->joursRestants(),
            'etat' => $abonnement->etatEcheance(),
            'mois' => $mois,
            'paiementDuMois' => $paiementDuMois,
            'demandeRenouvellement' => $demande !== null ? ['id' => $demande->id, 'reference' => $demande->reference, 'statut' => $demande->statut] : null,
        ];
    }

    private function nomSouscripteur(Abonnement $abonnement): string
    {
        if ($abonnement->client_id !== null) {
            return $abonnement->client->nom;
        }

        if ($abonnement->entreprise_id !== null) {
            return $abonnement->entreprise->nom;
        }

        return '—';
    }

    /**
     * @return array{nom?: string, contenu?: ?string, services_inclus?: list<string>, prix_affiche?: string, populaire?: bool, statut?: string}
     */
    private function validatedFormule(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'nom' => [$required, 'string', 'max:191'],
            'contenu' => ['nullable', 'string'],
            'services_inclus' => ['nullable', 'array'],
            'services_inclus.*' => ['string', 'max:191'],
            'prix_affiche' => [$required, 'string', 'max:191'],
            'populaire' => ['boolean'],
            'statut' => [$required, 'string', 'in:BROUILLON,PUBLIEE'],
        ]);
    }
}
