<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::command('notifications:rappels-seances')
    ->dailyAt('08:00')
    ->description('Remind enrolled apprenants of tomorrow\'s séances');

Schedule::command('notifications:relancer-contrats')
    ->dailyAt('09:00')
    ->description('Remind clients whose sent contract is still unsigned');
