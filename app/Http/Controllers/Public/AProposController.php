<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LogoPartenaire;
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
                'nom' => $membre->nomAvecCivilite(),
                'role' => $membre->role->label(),
            ]);

        $partenaires = LogoPartenaire::pourLeSite();

        return Inertia::render('public/a-propos', [
            'stats' => [
                'services' => Service::publies()->count(),
                // Les contrats en ligne restent un chiffre de communication,
                // comme les autres statistiques fixes de la vitrine publique
                // (voir docs/04-phase-3-espace-public.md). Les partenaires
                // suivent les logos affichés, l'équipe les comptes actifs.
                'partenariats' => count($partenaires),
                'contratsEnLigne' => 100,
                'membresEquipe' => MembreEquipe::where('statut', 'ACTIF')->count(),
            ],
            'equipe' => $equipe,
            'partenaires' => $partenaires,
        ]);
    }
}
