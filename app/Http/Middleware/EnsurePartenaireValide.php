<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the recruitment routes (offres, candidatures, profils proposés):
 * only an entreprise or an organisation with a recruitment subscription in
 * progress may reach them. One without it is sent to the subscription page;
 * a particulier has no recruitment space at all.
 */
class EnsurePartenaireValide
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user('web')?->client;

        abort_unless($client !== null && $client->estStructure(), 403);

        if (! $client->estPartenaireValide()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'Les pages recrutement s’ouvrent avec un abonnement : choisissez la durée qui vous convient.',
            ]);

            return to_route('espace-client.recrutement.abonnement');
        }

        return $next($request);
    }
}
