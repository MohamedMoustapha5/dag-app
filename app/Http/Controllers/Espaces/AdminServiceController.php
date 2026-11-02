<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminServiceController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;

        $services = Service::query()
            ->orderBy('categorie')
            ->orderBy('nom')
            ->get();

        return Inertia::render('espaces/admin-services', [
            ...$this->adminEspaceProps($membre),
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'nom' => $service->nom,
                'description' => $service->description,
                'categorie' => $service->categorie,
                'fournisseur' => $service->fournisseur,
                'image' => $service->image,
                'prix' => $service->prix,
                'statut' => $service->statut,
            ]),
            'categoriesExistantes' => $services
                ->pluck('categorie')
                ->unique()
                ->values(),
            'actions' => [
                'creer' => $membre->peut(Donnee::Catalogue, Action::Creer),
                'modifier' => $membre->peut(Donnee::Catalogue, Action::Modifier),
                'supprimer' => $membre->peut(Donnee::Catalogue, Action::Supprimer),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Creer), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'categorie' => ['required', 'string', 'max:191'],
            'fournisseur' => ['required', 'string', 'in:DAG,Nova-Tech'],
            'image' => ['nullable', 'string', 'max:255'],
            'prix' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['required', 'string', 'in:BROUILLON,PUBLIE'],
        ]);

        Service::create($data);

        return back();
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Modifier), 403);

        $data = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'max:191'],
            'description' => ['sometimes', 'required', 'string'],
            'categorie' => ['sometimes', 'required', 'string', 'max:191'],
            'fournisseur' => ['sometimes', 'required', 'string', 'in:DAG,Nova-Tech'],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'prix' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'statut' => ['sometimes', 'required', 'string', 'in:BROUILLON,PUBLIE'],
        ]);

        $service->update($data);

        return back();
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Supprimer), 403);

        $service->delete();

        return back();
    }
}
