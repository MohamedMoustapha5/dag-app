<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\CandidatureSpontanee;
use App\Models\EntreprisePartenaire;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminEntrepriseController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Voir), 403);

        $entreprises = EntreprisePartenaire::query()
            ->withCount('offresEmploi')
            ->orderByRaw("statut = 'EN_ATTENTE' desc")
            ->latest()
            ->get();

        $offres = OffreEmploi::query()
            ->with(['entreprise', 'candidatures'])
            ->orderByRaw("statut = 'BROUILLON' desc")
            ->latest()
            ->get();

        $peutPostuler = $this->peutPostuler($membre);

        return Inertia::render('espaces/admin-entreprises', [
            ...$this->adminEspaceProps($membre),
            'peutPostuler' => $peutPostuler,
            'apprenants' => $peutPostuler ? $this->apprenantsPostulables() : [],
            'peutValider' => $membre->peut(Donnee::EntreprisesOffres, Action::Valider),
            'peutModifier' => $membre->peut(Donnee::EntreprisesOffres, Action::Modifier),
            'entreprises' => $entreprises->map(fn (EntreprisePartenaire $entreprise) => [
                'id' => $entreprise->id,
                'nom' => $entreprise->nom,
                'secteur' => $entreprise->secteur,
                'email' => $entreprise->email,
                'telephone' => $entreprise->telephone,
                'statut' => $entreprise->statut,
                'offresCount' => $entreprise->offres_emploi_count,
                'auCompteClient' => $entreprise->client_id !== null,
            ]),
            'offres' => $offres->map(fn (OffreEmploi $offre) => [
                'id' => $offre->id,
                'titre' => $offre->titre,
                'entrepriseNom' => $offre->entreprise->nom,
                'typeContrat' => $offre->type_contrat,
                'ville' => $offre->ville,
                'statut' => $offre->statut,
                'candidaturesCount' => $offre->candidatures->count(),
                'postulable' => $this->estPostulable($offre),
                'dateLimite' => $offre->date_limite?->toDateString(),
                'expiree' => $offre->statut === 'PUBLIEE' && ! $this->estPostulable($offre),
                'apprenantIdsCandidats' => $offre->candidatures->pluck('apprenant_id')->values(),
            ]),
        ]);
    }

    /**
     * Dépose une candidature au nom d'un apprenant (nouveau diplômé ou
     * ancien apprenant déjà formé) sur une offre publiée. Le dépôt est
     * tracé (`deposee_par_id`) et soumis au consentement de partage de
     * l'apprenant, comme le CV transmis à l'entreprise.
     */
    public function postuler(Request $request, OffreEmploi $offreEmploi): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($this->peutPostuler($membre), 403);

        $data = $request->validate([
            'apprenant_id' => ['nullable', 'uuid', 'exists:apprenants,id', 'required_without:candidature_spontanee_id'],
            'candidature_spontanee_id' => ['nullable', 'uuid', 'exists:candidatures_spontanees,id', 'required_without:apprenant_id'],
        ]);

        abort_unless($this->estPostulable($offreEmploi), 409, "Cette offre n'accepte plus de candidatures.");

        $apprenant = DB::transaction(function () use ($data, $membre, $offreEmploi): Apprenant {
            $apprenant = $this->resoudreApprenant($data, $membre);

            if (! $apprenant->consentement_cv) {
                throw ValidationException::withMessages([
                    'apprenant_id' => "{$apprenant->user->name} n'a pas autorisé le partage de son dossier avec les entreprises.",
                ]);
            }

            if ($offreEmploi->candidatures()->where('apprenant_id', $apprenant->id)->exists()) {
                throw ValidationException::withMessages([
                    'apprenant_id' => "{$apprenant->user->name} a déjà postulé à cette offre.",
                ]);
            }

            $offreEmploi->candidatures()->create([
                'apprenant_id' => $apprenant->id,
                'statut' => 'DEPOSEE',
                'deposee_par_id' => $membre->id,
            ]);

            return $apprenant;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Candidature déposée pour {$apprenant->user->name} sur « {$offreEmploi->titre} ».",
        ]);

        return back();
    }

    /**
     * Soit un dossier apprenant existant, soit un dossier reçu par DAG
     * (candidature spontanée) que l'on transforme en dossier externe —
     * ce qui demande le droit de créer des dossiers apprenants.
     *
     * @param  array<string, mixed>  $data
     */
    private function resoudreApprenant(array $data, MembreEquipe $membre): Apprenant
    {
        if (! empty($data['candidature_spontanee_id'])) {
            abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Creer), 403);

            $recue = CandidatureSpontanee::where('id', $data['candidature_spontanee_id'])->firstOrFail();

            return $recue->versApprenant()->load('user');
        }

        return Apprenant::with('user')->where('id', $data['apprenant_id'])->firstOrFail();
    }

    private function peutPostuler(MembreEquipe $membre): bool
    {
        return $membre->peut(Donnee::EntreprisesOffres, Action::Creer)
            && $membre->peut(Donnee::DossiersApprenants, Action::Voir);
    }

    private function estPostulable(OffreEmploi $offre): bool
    {
        return $offre->statut === 'PUBLIEE'
            && ($offre->date_limite === null || ! $offre->date_limite->isPast() || $offre->date_limite->isToday());
    }

    /**
     * Tous les dossiers dont DAG dispose : apprenants (formés par DAG ou
     * non) et dossiers reçus par DAG pas encore transformés en dossier.
     *
     * @return array<int, array{id: string, source: string, nom: string, ville: string|null, estForme: bool, externe: bool, formations: array<int, string>, aUnCv: bool, consentement: bool}>
     */
    private function apprenantsPostulables(): array
    {
        $apprenants = Apprenant::query()
            ->with(['user', 'inscriptions.formation', 'attestations', 'candidatureSpontanee'])
            ->get()
            ->map(fn (Apprenant $apprenant) => [
                'id' => $apprenant->id,
                'source' => 'apprenant',
                'nom' => $apprenant->user->name,
                'ville' => $apprenant->ville,
                'estForme' => $apprenant->estForme(),
                'externe' => $apprenant->estExterne(),
                'formations' => $apprenant->inscriptions
                    ->whereIn('statut', ['VALIDEE', 'FORMEE'])
                    ->map(fn (Inscription $inscription) => $inscription->formation->nom)
                    ->unique()
                    ->values()
                    ->all(),
                'aUnCv' => $apprenant->cv_path !== null,
                'consentement' => $apprenant->consentement_cv,
            ]);

        $recus = CandidatureSpontanee::query()
            ->whereNull('apprenant_id')
            ->where('statut', 'NOUVELLE')
            ->get()
            ->map(fn (CandidatureSpontanee $recue) => [
                'id' => $recue->id,
                'source' => 'externe',
                'nom' => $recue->nom,
                'ville' => null,
                'estForme' => false,
                'externe' => true,
                'formations' => [],
                'aUnCv' => true,
                'consentement' => true,
            ]);

        return $apprenants->concat($recus)->sortBy('nom')->values()->all();
    }

    public function updateEntreprise(Request $request, EntreprisePartenaire $entreprisePartenaire): RedirectResponse
    {
        $membre = $this->membre($request);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['ACTIF', 'INACTIF'])],
        ]);

        $this->authorizeTransition($membre, $data['statut']);

        $entreprisePartenaire->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $data['statut'] === 'ACTIF'
                ? "{$entreprisePartenaire->nom} est maintenant un partenaire validé."
                : "{$entreprisePartenaire->nom} a été désactivé.",
        ]);

        return back();
    }

    public function updateOffre(Request $request, OffreEmploi $offreEmploi): RedirectResponse
    {
        $membre = $this->membre($request);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['PUBLIEE', 'CLOTUREE'])],
            'date_limite' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ]);

        $this->authorizeTransition($membre, $data['statut']);

        $offreEmploi->update($data);

        return back();
    }

    /**
     * Publier une offre ou valider un partenaire est une approbation
     * (Action::Valider) ; clôturer une offre ou désactiver un partenaire
     * est une gestion courante (Action::Modifier) — les deux sont gardées
     * séparément car la matrice de droits n'accorde pas toujours les deux
     * au même rôle (ex. Communication & marketing a Modifier mais pas
     * Valider sur Entreprises & offres).
     */
    private function authorizeTransition(MembreEquipe $membre, string $statut): void
    {
        $action = in_array($statut, ['ACTIF', 'PUBLIEE'], true) ? Action::Valider : Action::Modifier;

        abort_unless($membre->peut(Donnee::EntreprisesOffres, $action), 403);
    }
}
