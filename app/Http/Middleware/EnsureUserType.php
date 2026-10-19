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

        return $next($request);
    }
}
