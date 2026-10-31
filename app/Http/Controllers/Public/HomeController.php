<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MembreEquipe;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $equipe = MembreEquipe::where('statut', 'ACTIF')
            ->with('user')
            ->get()
            ->map(fn (MembreEquipe $membre) => [
                'nom' => $membre->user->name,
                'role' => $membre->role->label(),
            ]);

        return Inertia::render('public/home', [
            'stats' => [
                'services' => Service::publies()->count(),
            ],
            'equipe' => $equipe,
        ]);
    }
}
