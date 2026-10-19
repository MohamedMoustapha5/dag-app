<?php

use App\Http\Controllers\Espaces\AdminDashboardController;
use App\Http\Controllers\Espaces\ApprenantDashboardController;
use App\Http\Controllers\Espaces\ClientDashboardController;
use App\Http\Middleware\EnsureUserType;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Espaces DAG
|--------------------------------------------------------------------------
|
| One route group per account type (section 3.1 of the cahier des
| charges). Each is guarded by EnsureUserType so a client can never reach
| the apprenant or admin space, and vice versa. These sit outside the
| starter kit's {current_team} prefix — DAG accounts do not belong to a
| team.
|
| Entreprise partenaire is a business entity (App\Models\EntreprisePartenaire)
| managed directly by the admin team — it has no account type and no espace
| of its own, since a partner company exchanges with DAG through the membre
| de l'équipe who follows its file, not through self-service.
*/

Route::middleware(['auth', 'verified', EnsureUserType::class.':CLIENT'])
    ->prefix('espace-client')
    ->group(function () {
        Route::get('/', ClientDashboardController::class)->name('espace-client.dashboard');
    });

Route::middleware(['auth', 'verified', EnsureUserType::class.':APPRENANT'])
    ->prefix('espace-apprenant')
    ->group(function () {
        Route::get('/', ApprenantDashboardController::class)->name('espace-apprenant.dashboard');
    });

Route::middleware(['auth', 'verified', EnsureUserType::class.':MEMBRE_EQUIPE'])
    ->prefix('espace-admin')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('espace-admin.dashboard');
    });
