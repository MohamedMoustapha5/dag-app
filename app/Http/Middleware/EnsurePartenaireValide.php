<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the recruitment routes (offres, candidatures, profils proposés):
 * only an Entreprise client DAG has validated as an official recruitment
 * partner (a linked, ACTIF EntreprisePartenaire) may reach them.
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
        abort_unless($request->user()?->client?->estPartenaireValide(), 403);

        return $next($request);
    }
}
