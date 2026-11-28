<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Support\Referencement;
use Illuminate\Http\Response;

/**
 * The files search engines look for at the root of the site.
 */
class ReferencementController extends Controller
{
    /**
     * The public pages and every published formation, for search engines.
     */
    public function sitemap(): Response
    {
        $pages = collect(Referencement::routesPubliques())
            ->map(fn (string $route): array => ['url' => route($route), 'modifie' => null]);

        $formations = Formation::query()
            ->where('statut', 'PUBLIEE')
            ->orderBy('nom')
            ->get(['id', 'updated_at'])
            ->map(fn (Formation $formation): array => [
                'url' => route('formations.show', $formation),
                'modifie' => $formation->updated_at?->toAtomString(),
            ]);

        return response()
            ->view('referencement.sitemap', ['pages' => $pages->concat($formations)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Crawlers stay out of the private espaces and are shown the sitemap.
     */
    public function robots(): Response
    {
        $lignes = [
            'User-agent: *',
            'Disallow: /espace-admin',
            'Disallow: /espace-client',
            'Disallow: /espace-apprenant',
            'Disallow: /settings',
            'Disallow: /paiements',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lignes)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
