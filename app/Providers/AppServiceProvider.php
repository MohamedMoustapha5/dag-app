<?php

namespace App\Providers;

use App\Listeners\JournaliserNotification;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(NotificationSent::class, [JournaliserNotification::class, 'quandEnvoyee']);
        Event::listen(NotificationFailed::class, [JournaliserNotification::class, 'quandEchouee']);
    }
}
