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
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Statistiques, Action::Voir), 403);

        return Inertia::render('espaces/admin-statistiques', [
            ...$this->adminEspaceProps($membre),
            'activite' => [
                'apprenants' => Apprenant::query()->count(),
                'formationsPubliees' => Formation::query()->where('statut', 'PUBLIEE')->count(),
                'sessionsOuvertes' => FormationSession::query()->where('statut', 'OUVERTE')->count(),
                'entreprisesActives' => EntreprisePartenaire::query()->where('statut', 'ACTIF')->count(),
                'offresPubliees' => OffreEmploi::query()->where('statut', 'PUBLIEE')->count(),
                'abonnementsActifs' => Abonnement::query()->where('statut', 'ACTIF')->count(),
                'contratsActifs' => Contrat::query()->where('statut', 'ACTIF')->count(),
            ],
            'demandesParStatut' => Demande::query()
                ->selectRaw('statut, count(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'finances' => [
                'factureEnAttente' => (float) Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->sum('montant'),
                'factureEncaissee' => (float) Facture::query()->sum('montant_paye'),
                'facturesImpayeesCount' => Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->count(),
            ],
        ]);
    }
}
