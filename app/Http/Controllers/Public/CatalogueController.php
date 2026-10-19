<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function __invoke(): Response
    {
        $services = Service::publies()
            ->orderBy('categorie')
            ->orderBy('nom')
            ->get(['id', 'nom', 'description', 'categorie', 'fournisseur', 'image'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'nom' => $service->nom,
                'description' => $service->description,
                'categorie' => $service->categorie,
                'fournisseur' => $service->fournisseur,
                'image' => $service->image,
            ]);

        return Inertia::render('public/catalogue', [
            'services' => $services,
        ]);
    }
}
