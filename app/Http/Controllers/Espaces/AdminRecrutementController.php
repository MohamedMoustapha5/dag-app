<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\AccesRecrutement;
use App\Models\ActivityLog;
use App\Models\ForfaitRecrutement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The recruitment subscription seen by DAG: the forfaits the entreprises
 * choose from (their prices are set here) and every access period, paid
 * or offered.
 */
class AdminRecrutementController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::AbonnementsRecrutement, Action::Voir), 403);

        $aujourdhui = now()->startOfDay();

        $acces = AccesRecrutement::query()
            ->with(['client', 'offertPar', 'facture'])
            ->whereIn('statut', ['ACTIF', 'EN_ATTENTE_PAIEMENT'])
            ->latest()
            ->get();

        return Inertia::render('espaces/admin/recrutement', [
            ...$this->adminEspaceProps($membre),
            'forfaits' => ForfaitRecrutement::query()
                ->orderBy('ordre')
                ->get()
                ->map(fn (ForfaitRecrutement $forfait) => [
                    'id' => $forfait->id,
                    'libelle' => $forfait->libelle,
                    'dureeMois' => $forfait->duree_mois,
                    'prix' => $forfait->prix,
                ]),
            'acces' => $acces->map(fn (AccesRecrutement $ligne) => [
                'id' => $ligne->id,
                'clientNom' => $ligne->client?->nom ?? '—',
                'libelle' => $ligne->libelle,
                'origine' => $ligne->origine,
                'offertPar' => $ligne->offertPar?->name,
                'montant' => $ligne->montant,
                'debut' => $ligne->debut?->toDateString(),
                'fin' => $ligne->fin?->toDateString(),
                'factureReference' => $ligne->facture?->reference,
                'etat' => match (true) {
                    $ligne->statut === 'EN_ATTENTE_PAIEMENT' => 'EN_ATTENTE',
                    $ligne->debut->gt($aujourdhui) => 'A_VENIR',
                    $ligne->fin->lt($aujourdhui) => 'EXPIRE',
                    default => 'EN_COURS',
                },
            ]),
            'peutModifier' => $membre->peut(Donnee::AbonnementsRecrutement, Action::Valider),
        ]);
    }

    /**
     * A new price only applies to the subscriptions chosen from now on: a
     * facture already issued keeps its amount.
     */
    public function updateForfait(Request $request, ForfaitRecrutement $forfait): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::AbonnementsRecrutement, Action::Valider), 403);

        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:60'],
            'prix' => ['required', 'numeric', 'min:100', 'max:100000000'],
        ]);

        $ancienPrix = $forfait->prix;
        $forfait->update($data);

        ActivityLog::record($membre, 'a modifié le forfait recrutement', $forfait, "{$forfait->libelle} : {$ancienPrix} → {$forfait->prix} FCFA.");

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Forfait « {$forfait->libelle} » enregistré.",
        ]);

        return back();
    }
}
