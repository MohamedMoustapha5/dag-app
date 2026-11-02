<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\EntreprisePartenaire;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminEntrepriseController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
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

        return Inertia::render('espaces/admin-entreprises', [
            ...$this->adminEspaceProps($membre),
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
            ]),
        ]);
    }

    public function updateEntreprise(Request $request, EntreprisePartenaire $entreprisePartenaire): RedirectResponse
    {
        $membre = $request->user()->membreEquipe;

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
        $membre = $request->user()->membreEquipe;

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
