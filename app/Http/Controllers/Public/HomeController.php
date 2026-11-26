<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LogoPartenaire;
use App\Models\MembreEquipe;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $equipe = MembreEquipe::where('statut', 'ACTIF')
            ->where('visible_sur_le_site', true)
            ->get()
            ->map(fn (MembreEquipe $membre) => [
                'nom' => $membre->nomAvecCivilite(),
                'role' => $membre->role->label(),
            ]);

        return Inertia::render('public/home', [
            'stats' => [
                'services' => Service::publies()->count(),
            ],
            'equipe' => $equipe,
            'partenaires' => LogoPartenaire::pourLeSite(),
        ]);
    }
}
