<?php

namespace App\Providers;

use App\Listeners\JournaliserNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Every password form (sign-up, reset, settings, team accounts) uses
        // Password::default(). Production asks for more than the 8-character
        // minimum kept for local work and tests.
        Password::defaults(fn (): ?Password => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()
            : null);

        Event::listen(NotificationSent::class, [JournaliserNotification::class, 'quandEnvoyee']);
        Event::listen(NotificationFailed::class, [JournaliserNotification::class, 'quandEchouee']);

        // Contact, audit and spontaneous-application forms need no account:
        // without a limit, a script could flood the team and fill the disk
        // with uploaded CVs. Kept generous for shared connections.
        RateLimiter::for('formulaires-publics', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(50)->by('jour:'.$request->ip()),
        ]);

        Inertia::handleExceptionsUsing($this->pageErreur(...));
    }

    /**
     * Error page of the site, in French or English like the rest of the
     * interface, instead of Laravel's English one. Server errors keep
     * Laravel's debug page while APP_DEBUG is on; API calls keep JSON.
     */
    private function pageErreur(ExceptionResponse $response): ?ExceptionResponse
    {
        $statut = $response->statusCode();
        $erreurServeur = in_array($statut, [500, 503], true);

        if ($response->request->expectsJson()
            || ! in_array($statut, [403, 404, 419, 429, 500, 503], true)
            || ($erreurServeur && config('app.debug'))) {
            return null;
        }

        return $response->render('erreur', ['statut' => $statut]);
    }
}
