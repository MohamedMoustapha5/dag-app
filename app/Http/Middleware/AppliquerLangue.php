<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server messages (validation errors, sign-in and password reset messages)
 * follow the language chosen with the language switcher, kept in the
 * dag_lang cookie. French when the visitor has not chosen.
 */
class AppliquerLangue
{
    public const COOKIE = 'dag_lang';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($request->cookie(self::COOKIE) === 'en' ? 'en' : 'fr');

        return $next($request);
    }
}
