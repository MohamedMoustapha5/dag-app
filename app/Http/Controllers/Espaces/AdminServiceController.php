<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\DroitsMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminServiceController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;
        abort_unless($membre->peut(Donnee::Catalogue, Action::Voir), 403);

        $services = Service::query()
            ->orderBy('categorie')
            ->orderBy('nom')
            ->get();

        return Inertia::render('espaces/admin-services', [
            ...$this->adminEspaceProps($membre),
            'droitsResume' => DroitsMatrix::resume(Donnee::Catalogue),
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
            'fournisseursExistants' => $services
                ->pluck('fournisseur')
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

        $data = $this->validated($request);

        unset($data['image']);

        Service::create([
            ...$data,
            'image' => $this->storeImage($request),
        ]);

        return back();
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Modifier), 403);

        $data = $this->validated($request, sometimes: true);

        unset($data['image']);

        $imagePath = $this->storeImage($request);

        if ($imagePath !== null) {
            $data['image'] = $imagePath;
        }

        $service->update($data);

        return back();
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        abort_unless($request->user()->membreEquipe->peut(Donnee::Catalogue, Action::Supprimer), 403);

        $service->delete();

        return back();
    }

    /**
     * @return array{nom?: string, description?: string, categorie?: string, fournisseur?: string, image?: mixed, prix?: ?float, statut?: string}
     */
    private function validated(Request $request, bool $sometimes = false): array
    {
        $required = $sometimes ? 'sometimes' : 'required';

        return $request->validate([
            'nom' => [$required, 'string', 'max:191'],
            'description' => [$required, 'string'],
            'categorie' => [$required, 'string', 'max:191'],
            'fournisseur' => [$required, 'string', 'max:191'],
            'image' => ['nullable', 'image', 'max:5120'],
            'prix' => ['nullable', 'numeric', 'min:0'],
            'statut' => [$required, 'string', 'in:BROUILLON,PUBLIE'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('services', 'public');

        abort_if($path === false, 500, "Échec de l'enregistrement de l'image.");

        return Storage::disk('public')->url($path);
    }
}
