<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A signed-in client or apprenant never lands on the public site: whatever
 * brings them there (history, bookmark, link in an e-mail, typed address),
 * the public page is swapped for its counterpart in their own espace.
 * Visitors and DAG staff keep the public site as is.
 */
class GarderDansSonEspace
{
    /**
     * Espace page standing in for each public page, per account type. A page
     * missing from a list leads to that espace's dashboard.
     *
     * @var array<string, array<string, string>>
     */
    private const EQUIVALENTS = [
        'client' => [
            'catalogue' => 'espace-client.services',
            'grille-tarifaire' => 'espace-client.services',
            'abonnement' => 'espace-client.formules',
            'contact' => 'espace-client.support.index',
        ],
        'apprenant' => [
            'formations' => 'espace-apprenant.formations',
            'formations.show' => 'espace-apprenant.formations',
            'contact' => 'espace-apprenant.support.index',
        ],
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $request->isMethod('GET') || ! $user instanceof User || $user->type === null) {
            return $next($request);
        }

        $espace = $user->estApprenant() ? 'apprenant' : 'client';
        $page = $request->route()?->getName();
        $destination = self::EQUIVALENTS[$espace][$page] ?? "espace-{$espace}.dashboard";

        return redirect()->route($destination);
    }
}
