<?php

use App\Http\Middleware\AjouterEntetesSecurite;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'dag_lang']);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('espace-admin*')
                ? route('admin.connexion')
                : route('login'),
        );

        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Only APP_URL's host (and its subdomains) is accepted, so a forged Host
        // header can't make password-reset links point to another domain.
        $middleware->trustHosts();

        $middleware->append(AjouterEntetesSecurite::class);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
            // Every signed-in request, not only the routes that list
            // auth.session (Fortify's two-factor and passkey routes, payments,
            // invitations…): a session opened before a password change or
            // reset is signed out on its next request, wherever it goes.
            // It runs after the route's auth middleware (framework priority),
            // so the admin espace checks the admin guard.
            AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
