<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Apprenant;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\OffreEmploi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStatistiqueController extends Controller
{
    use SharesAdminEspaceProps;

    public function __invoke(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Statistiques, Action::Voir), 403);

        $peutVoir = fn (Donnee $donnee): bool => $membre->peut($donnee, Action::Voir);

        // Statistics are only computed for the domains the viewer may see;
        // the rest stays null so the page shows nothing about it.
        return Inertia::render('espaces/admin-statistiques', [
            ...$this->adminEspaceProps($membre),
            'activite' => [
                'apprenants' => $peutVoir(Donnee::DossiersApprenants) ? Apprenant::query()->count() : null,
                'formationsPubliees' => $peutVoir(Donnee::Formations) ? Formation::query()->where('statut', 'PUBLIEE')->count() : null,
                'sessionsOuvertes' => $peutVoir(Donnee::Formations) ? FormationSession::query()->where('statut', 'OUVERTE')->count() : null,
                'entreprisesActives' => $peutVoir(Donnee::EntreprisesOffres) ? EntreprisePartenaire::query()->where('statut', 'ACTIF')->count() : null,
                'offresPubliees' => $peutVoir(Donnee::EntreprisesOffres) ? OffreEmploi::query()->where('statut', 'PUBLIEE')->count() : null,
                'abonnementsActifs' => $peutVoir(Donnee::Abonnements) ? Abonnement::query()->where('statut', 'ACTIF')->count() : null,
                'contratsActifs' => $peutVoir(Donnee::ContratsClauses) ? Contrat::query()->where('statut', 'ACTIF')->count() : null,
            ],
            'demandesParStatut' => $peutVoir(Donnee::DemandesClients)
                ? Demande::query()
                    ->selectRaw('statut, count(*) as total')
                    ->groupBy('statut')
                    ->pluck('total', 'statut')
                : null,
            'finances' => $peutVoir(Donnee::FacturesPaiements) ? [
                'factureEnAttente' => (float) Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->sum('montant'),
                'factureEncaissee' => (float) Facture::query()->sum('montant_paye'),
                'facturesImpayeesCount' => Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->count(),
            ] : null,
        ]);
    }
}
