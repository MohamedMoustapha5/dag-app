<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Partenariat;
use App\Support\Partenariats\GestionPartenariats;
use App\Support\Partenariats\ParcoursPartenariat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The partnership page of an entreprise or an organisation: what a
 * partnership with DAG is, the request form, and where its partnership
 * stands (under review, contract to sign, in force…).
 */
class ClientPartenariatController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $request->user('web')->client;
        abort_unless($client->estStructure(), 403);

        $partenariats = $client->partenariats()->with('contrat')->latest()->get();
        $courant = $partenariats->first();

        return Inertia::render('espaces/client/partenariat', [
            'client' => $client->espaceHeaderProps(),
            'domaines' => collect(Partenariat::DOMAINES)
                ->map(fn (string $libelle, string $code) => ['code' => $code, 'libelle' => $libelle])
                ->values(),
            'partenariat' => $courant !== null ? $this->presenter($courant) : null,
            'peutDemander' => ! $client->partenariats()->ouverts()->exists(),
            'historique' => $partenariats->skip(1)->values()->map(fn (Partenariat $partenariat) => [
                'id' => $partenariat->id,
                'reference' => $partenariat->reference,
                'etape' => $partenariat->etape(),
                'creeLe' => $partenariat->created_at?->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request, GestionPartenariats $partenariats): RedirectResponse
    {
        $client = $request->user('web')->client;
        abort_unless($client->estStructure(), 403);

        $data = $request->validate([
            'domaines' => ['required', 'array', 'min:1'],
            'domaines.*' => ['string', 'distinct', Rule::in(array_keys(Partenariat::DOMAINES))],
            'message' => ['required', 'string', 'min:30', 'max:3000'],
        ], [
            'domaines.required' => 'Choisissez au moins un domaine de collaboration.',
            'domaines.min' => 'Choisissez au moins un domaine de collaboration.',
            'message.required' => 'Présentez votre structure et votre projet de partenariat.',
            'message.min' => 'Présentez votre projet en quelques phrases (30 caractères au moins).',
        ]);

        $partenariat = $partenariats->demander($client, $data['domaines'], $data['message']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Demande {$partenariat->reference} envoyée. L’équipe DAG l’étudie et revient vers vous rapidement.",
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function presenter(Partenariat $partenariat): array
    {
        return [
            'id' => $partenariat->id,
            'reference' => $partenariat->reference,
            'origine' => $partenariat->origine,
            'etape' => $partenariat->etape(),
            'parcours' => ParcoursPartenariat::pour($partenariat, vueClient: true),
            'domaines' => $partenariat->domaines,
            'message' => $partenariat->message,
            'duree' => $partenariat->dureeAffichee(),
            'debut' => $partenariat->debut?->toDateString(),
            'fin' => $partenariat->fin?->toDateString(),
            'motifRefus' => $partenariat->motif_refus,
            'creeLe' => $partenariat->created_at?->toDateString(),
            'contrat' => $partenariat->contrat !== null ? [
                'id' => $partenariat->contrat->id,
                'reference' => $partenariat->contrat->reference,
                'estSigne' => $partenariat->contrat->estSigne(),
            ] : null,
        ];
    }
}
