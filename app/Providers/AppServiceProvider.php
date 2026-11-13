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
    }
}
