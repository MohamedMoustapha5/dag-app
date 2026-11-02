<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Formule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminFormuleController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Abonnements, Action::Voir), 403);

        $formules = Formule::query()
            ->withCount('abonnements')
            ->orderByDesc('populaire')
            ->orderBy('nom')
            ->get();

        return Inertia::render('espaces/admin-formules', [
            ...$this->adminEspaceProps($membre),
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
            'actions' => [
                'creer' => $membre->peut(Donnee::Abonnements, Action::Creer),
                'modifier' => $membre->peut(Donnee::Abonnements, Action::Modifier),
                'supprimer' => $membre->peut(Donnee::Abonnements, Action::Supprimer),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Abonnements, Action::Creer), 403);

        $data = $this->validated($request);

        Formule::create($data);

        return back();
    }

    public function update(Request $request, Formule $formule): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Abonnements, Action::Modifier), 403);

        $data = $this->validated($request, sometimes: true);

        $formule->update($data);

        return back();
    }

    public function destroy(Request $request, Formule $formule): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Abonnements, Action::Supprimer), 403);

        $formule->delete();

        return back();
    }

    /**
     * @return array{nom?: string, contenu?: ?string, services_inclus?: list<string>, prix_affiche?: string, populaire?: bool, statut?: string}
     */
    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        $data = $request->validate([
            'nom' => [$required, 'string', 'max:191'],
            'contenu' => ['nullable', 'string'],
            'services_inclus' => ['nullable', 'string'],
            'prix_affiche' => [$required, 'string', 'max:191'],
            'populaire' => ['boolean'],
            'statut' => [$required, 'string', 'in:BROUILLON,PUBLIEE'],
        ]);

        if (array_key_exists('services_inclus', $data)) {
            $data['services_inclus'] = Str::of($data['services_inclus'] ?? '')
                ->explode("\n")
                ->map(fn (string $ligne) => trim($ligne))
                ->filter()
                ->values()
                ->all();
        }

        return $data;
    }
}
