<?php

use App\Http\Controllers\Public\AProposController;
use App\Http\Controllers\Public\AuditRequestController;
use App\Http\Controllers\Public\CandidatureSpontaneeController;
use App\Http\Controllers\Public\CatalogueController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FormationController;
use App\Http\Controllers\Public\FormuleController;
use App\Http\Controllers\Public\GrilleTarifaireController;
use App\Http\Controllers\Public\OffreEmploiController;
use App\Http\Middleware\GarderDansSonEspace;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Espace public
|--------------------------------------------------------------------------
|
| Section 6.1 du cahier des charges : catalogue, formations, formules et
| contact sont consultables par un visiteur sans compte. Les formulaires
| (audit gratuit, contact, candidature spontanée) n'exigent pas de compte
| non plus. Un client ou un apprenant connecté, lui, est renvoyé vers la
| page équivalente de son espace (GarderDansSonEspace).
*/

Route::middleware(GarderDansSonEspace::class)->group(function () {
    Route::get('catalogue', CatalogueController::class)->name('catalogue');

    Route::get('formations', [FormationController::class, 'index'])->name('formations');
    Route::get('formations/{formation}', [FormationController::class, 'show'])->name('formations.show');

    Route::get('abonnement', FormuleController::class)->name('abonnement');

    Route::get('grille-tarifaire', GrilleTarifaireController::class)->name('grille-tarifaire');

    Route::get('offres-emploi', OffreEmploiController::class)->name('offres-emploi');

    Route::get('a-propos', AProposController::class)->name('a-propos');

    Route::get('contact', [ContactController::class, 'index'])->name('contact');
});

/*
| Documents juridiques : consultables par tous, y compris un utilisateur
| connecté, puisqu'ils encadrent son compte.
*/
Route::inertia('conditions-generales-utilisation', 'public/conditions-utilisation')->name('conditions-utilisation');
Route::inertia('politique-de-confidentialite', 'public/politique-confidentialite')->name('politique-confidentialite');
Route::inertia('conditions-generales-vente', 'public/conditions-vente')->name('conditions-vente');
Route::inertia('mentions-legales', 'public/mentions-legales')->name('mentions-legales');

Route::middleware('throttle:formulaires-publics')->group(function () {
    Route::post('contact', [ContactController::class, 'store'])->name('contact.store');

    Route::post('audit-gratuit', [AuditRequestController::class, 'store'])->name('audit-requests.store');

    Route::post('candidatures-spontanees', [CandidatureSpontaneeController::class, 'store'])->name('candidatures-spontanees.store');
});
