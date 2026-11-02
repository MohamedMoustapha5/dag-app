<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    use SharesAdminEspaceProps;

    public function __invoke(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;

        $debutMois = now()->startOfMonth();
        $debutMoisDernier = now()->subMonthNoOverflow()->startOfMonth();
        $finMoisDernier = now()->subMonthNoOverflow()->endOfMonth();

        $chiffreAffairesMois = $this->sommePaiements($debutMois, now());
        $chiffreAffairesMoisDernier = $this->sommePaiements($debutMoisDernier, $finMoisDernier);

        $demandesEnAttente = Demande::query()->whereIn('statut', ['BROUILLON', 'EN_ETUDE'])->count();
        $aTransmettreNovaTech = Demande::query()
            ->where('est_partenaire_nova_tech', true)
            ->where('avancement_partenaire', 'A_TRANSMETTRE')
            ->count();

        $contratsASigner = Contrat::query()->where('statut', 'ENVOYE')->count();
        $relancesEnvoyees = Contrat::query()->where('statut', 'ENVOYE')->whereNotNull('derniere_relance_at')->count();

        $apprenantsFormes = Inscription::query()->where('statut', 'FORMEE')->distinct('apprenant_id')->count('apprenant_id');
        $apprenantsPlaces = Candidature::query()->where('statut', 'PLACEE')->count();
        $tauxPlacement = $apprenantsFormes > 0 ? (int) round($apprenantsPlaces / $apprenantsFormes * 100) : 0;

        return Inertia::render('espaces/admin-dashboard', [
            ...$this->adminEspaceProps($membre),
            'kpis' => [
                'chiffreAffairesMois' => $chiffreAffairesMois,
                'chiffreAffairesEvolution' => $this->variationPourcent($chiffreAffairesMoisDernier, $chiffreAffairesMois),
                'demandesEnAttente' => $demandesEnAttente,
                'aTransmettreNovaTech' => $aTransmettreNovaTech,
                'contratsASigner' => $contratsASigner,
                'relancesEnvoyees' => $relancesEnvoyees,
                'tauxPlacement' => $tauxPlacement,
                'apprenantsPlaces' => $apprenantsPlaces,
            ],
            'paiements' => [
                '7j' => $this->serieQuotidienne(7),
                '30j' => $this->serieHebdomadaire(30),
                '3mois' => $this->serieMensuelle(3),
            ],
            'repartitionCa' => $this->repartitionChiffreAffaires($debutMois),
            'servicesPlusDemandes' => $this->servicesPlusDemandes($debutMois),
        ]);
    }

    private function sommePaiements(Carbon $debut, Carbon $fin): float
    {
        return (float) Paiement::query()
            ->where('statut', 'CONFIRME')
            ->whereBetween('date_paiement', [$debut, $fin])
            ->sum('montant');
    }

    private function variationPourcent(float $avant, float $apres): int
    {
        if ($avant <= 0.0) {
            return $apres > 0.0 ? 100 : 0;
        }

        return (int) round(($apres - $avant) / $avant * 100);
    }

    /**
     * @return array<int, array{label: string, montant: float}>
     */
    private function serieQuotidienne(int $jours): array
    {
        $jourLabels = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

        return collect(range($jours - 1, 0))
            ->map(function (int $decalage) use ($jourLabels) {
                $jour = now()->subDays($decalage);

                return [
                    'label' => $jourLabels[(int) $jour->dayOfWeek],
                    'montant' => $this->sommePaiements($jour->copy()->startOfDay(), $jour->copy()->endOfDay()),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, montant: float}>
     */
    private function serieHebdomadaire(int $jours): array
    {
        $semaines = (int) ceil($jours / 7);

        return collect(range($semaines - 1, 0))
            ->map(function (int $decalage) {
                $finSemaine = now()->subWeeks($decalage)->endOfDay();
                $debutSemaine = $finSemaine->copy()->subDays(6)->startOfDay();

                return [
                    'label' => $debutSemaine->format('d/m'),
                    'montant' => $this->sommePaiements($debutSemaine, $finSemaine),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, montant: float}>
     */
    private function serieMensuelle(int $mois): array
    {
        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        return collect(range($mois - 1, 0))
            ->map(function (int $decalage) use ($moisLabels) {
                $moisCourant = now()->subMonthsNoOverflow($decalage);

                return [
                    'label' => $moisLabels[$moisCourant->month - 1],
                    'montant' => $this->sommePaiements($moisCourant->copy()->startOfMonth(), $moisCourant->copy()->endOfMonth()),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{total: float, segments: list<array{label: string, montant: float}>}
     */
    private function repartitionChiffreAffaires(Carbon $debutMois): array
    {
        $paiements = Paiement::query()
            ->where('statut', 'CONFIRME')
            ->whereBetween('date_paiement', [$debutMois, now()])
            ->with('facture')
            ->get();

        $services = 0.0;
        $abonnements = 0.0;
        $formations = 0.0;

        foreach ($paiements as $paiement) {
            match ($paiement->facture?->type) {
                'ABONNEMENT' => $abonnements += (float) $paiement->montant,
                'FORMATION' => $formations += (float) $paiement->montant,
                default => $services += (float) $paiement->montant,
            };
        }

        return [
            'total' => $services + $abonnements + $formations,
            'segments' => [
                ['label' => 'Services', 'montant' => $services],
                ['label' => 'Abonnements', 'montant' => $abonnements],
                ['label' => 'Formations', 'montant' => $formations],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, total: int, novaTech: bool}>
     */
    private function servicesPlusDemandes(Carbon $debutMois): array
    {
        /** @var Collection<int, Demande> $demandes */
        $demandes = Demande::query()
            ->whereNotNull('service_id')
            ->where('created_at', '>=', $debutMois)
            ->with('service')
            ->get();

        return $demandes
            ->filter(fn (Demande $demande) => $demande->service !== null)
            ->groupBy(fn (Demande $demande) => $demande->service->nom)
            ->map(fn (Collection $groupe, string $nom) => [
                'label' => $nom,
                'total' => $groupe->count(),
                'novaTech' => $groupe->first()->service->fournisseur === 'Nova-Tech',
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();
    }
}
