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

            ->get()
            ->map(fn (MembreEquipe $membre) => [
                'nom' => $membre->name,
                'role' => $membre->role->label(),
            ]);

        return Inertia::render('public/a-propos', [
            'stats' => [
                'services' => Service::publies()->count(),
                // Partenariats techniques et contrats en ligne : chiffres de
                // communication, comme les autres statistiques fixes de la
                // vitrine publique (voir docs/04-phase-3-espace-public.md). La
                // taille de l'équipe, elle, suit les comptes actifs.
                'partenariats' => 3,
                'contratsEnLigne' => 100,
                'membresEquipe' => MembreEquipe::where('statut', 'ACTIF')->count(),
            ],
            'equipe' => $equipe,
        ]);
    }
}
