<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientServiceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        return Inertia::render('espaces/client/services-index', [
            'client' => $client->espaceHeaderProps(),
            'services' => Service::publies()
                ->orderBy('categorie')
                ->orderBy('nom')
                ->get(['id', 'nom', 'description', 'categorie', 'image']),
        ]);
    }
}
