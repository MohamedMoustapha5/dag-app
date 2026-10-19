<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user()->apprenant;

        return Inertia::render('espaces/apprenant-dashboard', [
            'apprenant' => [
                'ville' => $apprenant->ville,
                'inscriptionsCount' => $apprenant->inscriptions()->count(),
                'candidaturesCount' => $apprenant->candidatures()->count(),
            ],
        ]);
    }
}
