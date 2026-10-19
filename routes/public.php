<?php

use App\Http\Controllers\Public\AProposController;
use App\Http\Controllers\Public\AuditRequestController;
use App\Http\Controllers\Public\CatalogueController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FormationController;
use App\Http\Controllers\Public\FormuleController;
use App\Http\Controllers\Public\OffreEmploiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Espace public
|--------------------------------------------------------------------------
|
| Section 6.1 du cahier des charges : catalogue, formations, formules et
| contact sont consultables par un visiteur sans compte. Les formulaires
| (audit gratuit, contact) n'exigent pas de compte non plus.
*/

Route::get('catalogue', CatalogueController::class)->name('catalogue');

Route::get('formations', [FormationController::class, 'index'])->name('formations');
Route::get('formations/{formation}', [FormationController::class, 'show'])->name('formations.show');

Route::get('abonnement', FormuleController::class)->name('abonnement');

Route::get('offres-emploi', OffreEmploiController::class)->name('offres-emploi');

Route::get('a-propos', AProposController::class)->name('a-propos');

Route::get('contact', [ContactController::class, 'index'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');

Route::post('audit-gratuit', [AuditRequestController::class, 'store'])->name('audit-requests.store');
