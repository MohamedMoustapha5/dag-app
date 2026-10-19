<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Support\DroitsMatrix;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;

        return Inertia::render('espaces/admin-dashboard', [
            'membre' => [
                'role' => $membre->role->value,
                'roleLabel' => $membre->role->label(),
            ],
            'droits' => collect(DroitsMatrix::pourRole($membre->role))
                ->map(fn ($niveau, $donnee) => $niveau->value),
        ]);
    }
}
