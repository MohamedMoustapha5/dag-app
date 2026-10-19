<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user()->client;

        return Inertia::render('espaces/client-dashboard', [
            'client' => [
                'nom' => $client->nom,
                'type' => $client->type,
                'statut' => $client->statut,
            ],
        ]);
    }
}
