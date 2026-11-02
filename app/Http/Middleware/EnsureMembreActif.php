<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated membre d'équipe loses access immediately, not only on their
 * next login attempt: an already-open admin session must not keep working
 * after revocation (section 6.9 du cahier des charges).
 */
class EnsureMembreActif
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $membre = Auth::guard('admin')->user();

        abort_if($membre === null || ! $membre->estActif(), 403);

        return $next($request);
    }
}
