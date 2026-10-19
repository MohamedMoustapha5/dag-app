<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MembreEquipe;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class AProposController extends Controller
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

        return Inertia::render('public/a-propos', [
            'stats' => [
                'services' => Service::publies()->count(),
                // Partenariats techniques, contrats en ligne et taille
                // d'équipe affichée : chiffres de communication, comme les
                // autres statistiques fixes de la vitrine publique (voir
                // docs/04-phase-3-espace-public.md).
                'partenariats' => 3,
                'contratsEnLigne' => 100,
                'membresEquipe' => 6,
            ],
            'equipe' => $equipe,
        ]);
    }
}
