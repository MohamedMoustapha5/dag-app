<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\EntreprisePartenaire;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        abort_unless($this->peutPostuler($this->membre($request)), 403);

        $data = $request->validate([
            'apprenant_id' => ['required', 'uuid', 'exists:apprenants,id'],
        ]);

        abort_unless($this->estPostulable($offreEmploi), 409, "Cette offre n'accepte plus de candidatures.");

        $apprenant = Apprenant::with('user')->where('id', $data['apprenant_id'])->firstOrFail();

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
            'deposee_par_id' => $this->membre($request)->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Candidature déposée pour {$apprenant->user->name} sur « {$offreEmploi->titre} ».",
        ]);

        return back();
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
     * Tous les dossiers apprenants, anciens apprenants déjà formés inclus.
     *
     * @return Collection<int, array{id: string, nom: string, ville: string|null, estForme: bool, formations: Collection<int, string>, aUnCv: bool, consentement: bool}>
     */
    private function apprenantsPostulables(): Collection
    {
        return Apprenant::query()
            ->with(['user', 'inscriptions.formation', 'attestations'])
            ->get()
            ->sortBy(fn (Apprenant $apprenant) => $apprenant->user->name)
            ->values()
            ->map(fn (Apprenant $apprenant) => [
                'id' => $apprenant->id,
                'nom' => $apprenant->user->name,
                'ville' => $apprenant->ville,
                'estForme' => $apprenant->estForme(),
                'formations' => $apprenant->inscriptions
                    ->whereIn('statut', ['VALIDEE', 'FORMEE'])
                    ->map(fn (Inscription $inscription) => $inscription->formation->nom)
                    ->unique()
                    ->values(),
                'aUnCv' => $apprenant->cv_path !== null,
                'consentement' => $apprenant->consentement_cv,
            ]);
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
