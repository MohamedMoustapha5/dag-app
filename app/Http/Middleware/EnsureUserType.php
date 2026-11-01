<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to a single account type (client, apprenant or
 * membre de l'équipe DAG). Each account type has its own space, with no
 * overlap — unlike the starter kit's team switcher, a user never has more
 * than one type.
 */
class EnsureUserType
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        abort_if(! $user, 403);
        abort_if($user->type !== UserType::from($type), 403);
        // A deactivated membre d'équipe loses access immediately, not only
        // on their next login attempt — an already-open session must not
        // keep working after revocation (section 6.9 du cahier des charges).
        abort_if(! $user->peutSeConnecter(), 403);

        return $next($request);
    }
}
