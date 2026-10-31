<?php

use App\Http\Controllers\Espaces\AdminDashboardController;
use App\Http\Controllers\Espaces\AdminGrilleTarifaireController;
use App\Http\Controllers\Espaces\ApprenantDashboardController;
use App\Http\Controllers\Espaces\ClientAbonnementController;
use App\Http\Controllers\Espaces\ClientCandidatureController;
use App\Http\Controllers\Espaces\ClientContratController;
use App\Http\Controllers\Espaces\ClientDashboardController;
use App\Http\Controllers\Espaces\ClientDemandeController;
use App\Http\Controllers\Espaces\ClientDocumentController;
use App\Http\Controllers\Espaces\ClientFactureController;
use App\Http\Controllers\Espaces\ClientOffreController;
use App\Http\Controllers\Espaces\ClientPaiementController;
use App\Http\Controllers\Espaces\ClientParametreController;
use App\Http\Controllers\Espaces\ClientProfilProposeController;
use App\Http\Controllers\Espaces\ClientServiceController;
use App\Http\Middleware\EnsurePartenaireValide;
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
| Entreprise partenaire (App\Models\EntreprisePartenaire) is still mostly
| managed by DAG staff directly, but a self-registered Entreprise client
| can now be linked to one once validated as an official recruitment
| partner (Client::estPartenaireValide()) — only then do the recruitment
| routes below (offres, candidatures, profils-proposes) unlock, guarded by
| EnsurePartenaireValide on top of the regular CLIENT type check.
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
        Route::get('paiements', ClientPaiementController::class)->name('espace-client.paiements');

        Route::middleware(EnsurePartenaireValide::class)->group(function () {
            Route::get('offres', [ClientOffreController::class, 'index'])->name('espace-client.offres.index');
            Route::get('offres/creer', [ClientOffreController::class, 'create'])->name('espace-client.offres.create');
            Route::post('offres', [ClientOffreController::class, 'store'])->name('espace-client.offres.store');
            Route::get('offres/{offre}', [ClientOffreController::class, 'show'])->name('espace-client.offres.show');
            Route::get('candidatures', [ClientCandidatureController::class, 'index'])->name('espace-client.candidatures.index');
            Route::patch('candidatures/{candidature}', [ClientCandidatureController::class, 'update'])->name('espace-client.candidatures.update');
            Route::get('profils-proposes', [ClientProfilProposeController::class, 'index'])->name('espace-client.profils-proposes.index');
            Route::patch('profils-proposes/{profil}', [ClientProfilProposeController::class, 'update'])->name('espace-client.profils-proposes.update');
        });
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
        Route::get('grille-tarifaire', [AdminGrilleTarifaireController::class, 'index'])->name('espace-admin.grille-tarifaire.index');
        Route::post('grille-tarifaire', [AdminGrilleTarifaireController::class, 'store'])->name('espace-admin.grille-tarifaire.store');
        Route::patch('grille-tarifaire/{ligneTarifaire}', [AdminGrilleTarifaireController::class, 'update'])->name('espace-admin.grille-tarifaire.update');
        Route::delete('grille-tarifaire/{ligneTarifaire}', [AdminGrilleTarifaireController::class, 'destroy'])->name('espace-admin.grille-tarifaire.destroy');
    });
