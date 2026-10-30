<?php

use App\Http\Controllers\Espaces\AdminDashboardController;
use App\Http\Controllers\Espaces\ApprenantDashboardController;
use App\Http\Controllers\Espaces\ClientAbonnementController;
use App\Http\Controllers\Espaces\ClientContratController;
use App\Http\Controllers\Espaces\ClientDashboardController;
use App\Http\Controllers\Espaces\ClientDemandeController;
use App\Http\Controllers\Espaces\ClientDocumentController;
use App\Http\Controllers\Espaces\ClientFactureController;
use App\Http\Controllers\Espaces\ClientParametreController;
use App\Http\Controllers\Espaces\ClientServiceController;
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
        Route::get('services', ClientServiceController::class)->name('espace-client.services');
        Route::get('demandes', [ClientDemandeController::class, 'index'])->name('espace-client.demandes.index');
        Route::get('demandes/creer', [ClientDemandeController::class, 'create'])->name('espace-client.demandes.create');
        Route::post('demandes', [ClientDemandeController::class, 'store'])->name('espace-client.demandes.store');
        Route::get('demandes/{demande}', [ClientDemandeController::class, 'show'])->name('espace-client.demandes.show');
        Route::get('abonnements', ClientAbonnementController::class)->name('espace-client.abonnements');
        Route::get('factures', ClientFactureController::class)->name('espace-client.factures');
        Route::get('contrats', ClientContratController::class)->name('espace-client.contrats');
        Route::get('documents', ClientDocumentController::class)->name('espace-client.documents');
        Route::get('parametres', [ClientParametreController::class, 'edit'])->name('espace-client.parametres');
        Route::patch('parametres', [ClientParametreController::class, 'update'])->name('espace-client.parametres.update');
        Route::patch('parametres/notifications', [ClientParametreController::class, 'updateNotifications'])->name('espace-client.parametres.notifications');
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
