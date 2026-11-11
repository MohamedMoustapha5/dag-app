<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Decaissement;
use App\Support\Paiements\DecaissementService;
use App\Support\Paiements\PasserelleIndisponible;
use App\Support\Paiements\TelephoneMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Retraits: DAG withdraws money from its online payment provider balance
 * to a mobile money account. Sending money out needs the Valider right on
 * factures & paiements; seeing the history only needs Voir.
 */
class AdminRetraitController extends Controller
{
    use SharesAdminEspaceProps;

    public function __construct(private DecaissementService $decaissements) {}

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Voir), 403);

        /** @var array<string, string> $moyens */
        $moyens = config('paiements.moyens_decaissement');
        $options = [];

        foreach ($moyens as $valeur => $label) {
            $options[] = ['value' => $valeur, 'label' => $label];
        }

        return Inertia::render('espaces/admin-retraits', [
            ...$this->adminEspaceProps($membre),
            'peutRetirer' => $membre->peut(Donnee::FacturesPaiements, Action::Valider),
            'retraitsDisponibles' => $this->decaissements->disponible(),
            'moyens' => $options,
            'retraits' => Decaissement::query()
                ->with('demandePar')
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (Decaissement $decaissement) => [
                    'id' => $decaissement->id,
                    'montant' => $decaissement->montant,
                    'moyen' => $moyens[$decaissement->moyen] ?? $decaissement->moyen,
                    'telephone' => $decaissement->telephone,
                    'beneficiaire' => $decaissement->beneficiaire,
                    'motif' => $decaissement->motif,
                    'statut' => $decaissement->statut,
                    'motifEchec' => $decaissement->motif_echec,
                    'reference' => $decaissement->reference_transaction,
                    'demandePar' => $decaissement->demandePar?->name,
                    'demandeLe' => $decaissement->created_at?->format('d/m/Y H:i'),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Valider), 403);

        $data = $request->validate([
            'montant' => ['required', 'integer', 'min:100', 'max:100000000'],
            'moyen' => ['required', Rule::in(array_keys(config('paiements.moyens_decaissement')))],
            'telephone' => ['required', 'string', 'max:30'],
            'beneficiaire' => ['required', 'string', 'max:120'],
            'motif' => ['required', 'string', 'max:250'],
        ]);

        $telephone = TelephoneMobile::normaliser($data['telephone']);

        if ($telephone === null) {
            throw ValidationException::withMessages([
                'telephone' => 'Saisissez un numéro mobile camerounais valide (ex. 6 90 00 00 00).',
            ]);
        }

        try {
            $decaissement = $this->decaissements->demander((float) $data['montant'], $data['moyen'], $telephone, $data['beneficiaire'], $data['motif'], $membre);
        } catch (PasserelleIndisponible $erreur) {
            throw ValidationException::withMessages(['montant' => $erreur->getMessage()]);
        }

        Inertia::flash('toast', match ($decaissement->statut) {
            'CONFIRME' => ['type' => 'success', 'message' => 'Retrait effectué.'],
            'ECHEC' => ['type' => 'error', 'message' => 'Le retrait a échoué : '.($decaissement->motif_echec ?? 'refusé par le prestataire.')],
            default => ['type' => 'success', 'message' => 'Retrait envoyé au prestataire : il sera confirmé dans quelques instants.'],
        });

        return back();
    }

    /**
     * Asks the provider about a retrait whose notification has not arrived.
     */
    public function actualiser(Request $request, Decaissement $decaissement): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::FacturesPaiements, Action::Valider), 403);

        try {
            $this->decaissements->actualiser($decaissement);
        } catch (PasserelleIndisponible $erreur) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $erreur->getMessage()]);

            return back();
        }

        Inertia::flash('toast', match ($decaissement->fresh()->statut) {
            'CONFIRME' => ['type' => 'success', 'message' => 'Retrait confirmé.'],
            'ECHEC' => ['type' => 'error', 'message' => 'Le retrait a échoué.'],
            default => ['type' => 'success', 'message' => 'Le retrait est toujours en cours chez le prestataire.'],
        });

        return back();
    }
}
