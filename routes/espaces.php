<?php

use App\Http\Controllers\Espaces\AdminAbonnementController;
use App\Http\Controllers\Espaces\AdminApprenantController;
use App\Http\Controllers\Espaces\AdminAttestationController;
use App\Http\Controllers\Espaces\AdminAuthController;
use App\Http\Controllers\Espaces\AdminCandidatExterneController;
use App\Http\Controllers\Espaces\AdminClientController;
use App\Http\Controllers\Espaces\AdminContratController;
use App\Http\Controllers\Espaces\AdminDashboardController;
use App\Http\Controllers\Espaces\AdminDemandeController;
use App\Http\Controllers\Espaces\AdminEntrepriseController;
use App\Http\Controllers\Espaces\AdminEquipeController;
use App\Http\Controllers\Espaces\AdminFactureController;
use App\Http\Controllers\Espaces\AdminFormationController;
use App\Http\Controllers\Espaces\AdminGrilleTarifaireController;
use App\Http\Controllers\Espaces\AdminInscriptionController;
use App\Http\Controllers\Espaces\AdminSeanceController;
use App\Http\Controllers\Espaces\AdminServiceController;
use App\Http\Controllers\Espaces\AdminStatistiqueController;
use App\Http\Controllers\Espaces\ApprenantDashboardController;
use App\Http\Controllers\Espaces\ApprenantDossierController;
use App\Http\Controllers\Espaces\ApprenantFactureController;
use App\Http\Controllers\Espaces\ApprenantFormationController;
use App\Http\Controllers\Espaces\ApprenantInscriptionController;
use App\Http\Controllers\Espaces\ApprenantProfilController;
use App\Http\Controllers\Espaces\ApprenantProgressionController;
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
use App\Http\Controllers\Espaces\ClientServiceController;
use App\Http\Controllers\Espaces\PaiementEnLigneController;
use App\Http\Controllers\PaiementWebhookController;
use App\Http\Middleware\EnsureMembreActif;
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
| routes below (offres, candidatures) unlock, guarded by
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
        Route::get('factures/{facture}/pdf', [ClientFactureController::class, 'pdf'])->name('espace-client.factures.pdf');
        Route::get('contrats', ClientContratController::class)->name('espace-client.contrats');
        Route::get('contrats/{contrat}', [ClientContratController::class, 'show'])->name('espace-client.contrats.show');
        Route::post('contrats/{contrat}/signature', [ClientContratController::class, 'signer'])->name('espace-client.contrats.signer');
        Route::get('contrats/{contrat}/pdf', [ClientContratController::class, 'pdf'])->name('espace-client.contrats.pdf');
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
            Route::get('candidatures/{candidature}', [ClientCandidatureController::class, 'show'])->name('espace-client.candidatures.show');
            Route::get('candidatures/{candidature}/cv', [ClientCandidatureController::class, 'cv'])->name('espace-client.candidatures.cv');
            Route::patch('candidatures/{candidature}', [ClientCandidatureController::class, 'update'])->name('espace-client.candidatures.update');
        });
    });

Route::middleware(['auth', 'verified', EnsureUserType::class.':APPRENANT'])
    ->prefix('espace-apprenant')
    ->group(function () {
        Route::get('/', ApprenantDashboardController::class)->name('espace-apprenant.dashboard');
        Route::get('formations', ApprenantFormationController::class)->name('espace-apprenant.formations');
        Route::get('inscriptions', ApprenantInscriptionController::class)->name('espace-apprenant.inscriptions');
        Route::get('factures', ApprenantFactureController::class)->name('espace-apprenant.factures');
        Route::get('factures/{facture}/pdf', [ApprenantFactureController::class, 'pdf'])->name('espace-apprenant.factures.pdf');
        Route::get('dossier', [ApprenantDossierController::class, 'edit'])->name('espace-apprenant.dossier');
        Route::patch('dossier', [ApprenantDossierController::class, 'update'])->name('espace-apprenant.dossier.update');
        Route::get('attestations/{attestation}/pdf', [ApprenantDossierController::class, 'attestationPdf'])->name('espace-apprenant.attestations.pdf');
        Route::get('progression', [ApprenantProgressionController::class, 'index'])->name('espace-apprenant.progression');
        Route::get('progression/{inscription}', [ApprenantProgressionController::class, 'show'])->name('espace-apprenant.progression.show');
        Route::get('profil', [ApprenantProfilController::class, 'edit'])->name('espace-apprenant.profil');
        Route::patch('profil', [ApprenantProfilController::class, 'update'])->name('espace-apprenant.profil.update');
        Route::patch('profil/notifications', [ApprenantProfilController::class, 'updateNotifications'])->name('espace-apprenant.profil.notifications');
    });

/*
| Staff accounts sign in on their own guard (`admin`, table
| membres_equipe), apart from the public login.
*/
Route::prefix('espace-admin')->group(function () {
    Route::get('connexion', [AdminAuthController::class, 'create'])->name('admin.connexion');
    Route::post('connexion', [AdminAuthController::class, 'store'])->name('admin.connexion.store');
    Route::post('deconnexion', [AdminAuthController::class, 'destroy'])->middleware('auth:admin')->name('admin.deconnexion');
});

Route::middleware(['auth:admin', EnsureMembreActif::class])
    ->prefix('espace-admin')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('espace-admin.dashboard');
        Route::get('services', [AdminServiceController::class, 'index'])->name('espace-admin.services.index');
        Route::post('services', [AdminServiceController::class, 'store'])->name('espace-admin.services.store');
        Route::patch('services/{service}', [AdminServiceController::class, 'update'])->name('espace-admin.services.update');
        Route::delete('services/{service}', [AdminServiceController::class, 'destroy'])->name('espace-admin.services.destroy');
        Route::get('grille-tarifaire', [AdminGrilleTarifaireController::class, 'index'])->name('espace-admin.grille-tarifaire.index');
        Route::post('grille-tarifaire', [AdminGrilleTarifaireController::class, 'store'])->name('espace-admin.grille-tarifaire.store');
        Route::patch('grille-tarifaire/{ligneTarifaire}', [AdminGrilleTarifaireController::class, 'update'])->name('espace-admin.grille-tarifaire.update');
        Route::delete('grille-tarifaire/{ligneTarifaire}', [AdminGrilleTarifaireController::class, 'destroy'])->name('espace-admin.grille-tarifaire.destroy');
        Route::get('attestations', [AdminAttestationController::class, 'index'])->name('espace-admin.attestations.index');
        Route::post('attestations', [AdminAttestationController::class, 'store'])->name('espace-admin.attestations.store');
        Route::get('attestations/{attestation}/pdf', [AdminAttestationController::class, 'pdf'])->name('espace-admin.attestations.pdf');
        Route::get('equipe', [AdminEquipeController::class, 'index'])->name('espace-admin.equipe.index');
        Route::post('equipe', [AdminEquipeController::class, 'store'])->name('espace-admin.equipe.store');
        Route::patch('equipe/{membreEquipe}', [AdminEquipeController::class, 'update'])->name('espace-admin.equipe.update');
        Route::put('equipe-droits', [AdminEquipeController::class, 'updateDroit'])->name('espace-admin.equipe.droits.update');
        Route::delete('equipe-droits/{role}', [AdminEquipeController::class, 'reinitialiserDroits'])->name('espace-admin.equipe.droits.reset');
        Route::get('entreprises', [AdminEntrepriseController::class, 'index'])->name('espace-admin.entreprises.index');
        Route::patch('entreprises/{entreprisePartenaire}', [AdminEntrepriseController::class, 'updateEntreprise'])->name('espace-admin.entreprises.update');
        Route::patch('offres/{offreEmploi}', [AdminEntrepriseController::class, 'updateOffre'])->name('espace-admin.offres.update');
        Route::post('offres/{offreEmploi}/candidatures', [AdminEntrepriseController::class, 'postuler'])->name('espace-admin.offres.postuler');
        Route::get('demandes', [AdminDemandeController::class, 'index'])->name('espace-admin.demandes.index');
        Route::get('demandes/{demande}', [AdminDemandeController::class, 'show'])->name('espace-admin.demandes.show');
        Route::patch('demandes/{demande}', [AdminDemandeController::class, 'update'])->name('espace-admin.demandes.update');
        Route::post('demandes/{demande}/devis', [AdminDemandeController::class, 'envoyerDevis'])->name('espace-admin.demandes.devis');
        Route::get('clients', [AdminClientController::class, 'index'])->name('espace-admin.clients.index');
        Route::delete('clients/{client}', [AdminClientController::class, 'destroy'])->name('espace-admin.clients.destroy');
        Route::get('formations', [AdminFormationController::class, 'index'])->name('espace-admin.formations.index');
        Route::post('sessions', [AdminFormationController::class, 'storeRapide'])->name('espace-admin.sessions.store-rapide');
        Route::get('formations/{formation}', [AdminFormationController::class, 'show'])->name('espace-admin.formations.show');
        Route::patch('formations/{formation}', [AdminFormationController::class, 'update'])->name('espace-admin.formations.update');
        Route::post('formations/{formation}/sessions', [AdminFormationController::class, 'storeSession'])->name('espace-admin.formations.sessions.store');
        Route::patch('sessions/{formationSession}', [AdminFormationController::class, 'updateSession'])->name('espace-admin.sessions.update');
        Route::delete('sessions/{formationSession}', [AdminFormationController::class, 'destroySession'])->name('espace-admin.sessions.destroy');
        Route::get('sessions/{formationSession}', [AdminSeanceController::class, 'show'])->name('espace-admin.sessions.show');
        Route::get('sessions/{formationSession}/inscrits', [AdminInscriptionController::class, 'index'])->name('espace-admin.sessions.inscrits');
        Route::get('sessions/{formationSession}/export', [AdminInscriptionController::class, 'export'])->name('espace-admin.sessions.export');
        Route::post('sessions/{formationSession}/seances', [AdminSeanceController::class, 'store'])->name('espace-admin.sessions.seances.store');
        Route::delete('seances/{seance}', [AdminSeanceController::class, 'destroy'])->name('espace-admin.seances.destroy');
        Route::patch('inscriptions/{inscription}/valider', [AdminInscriptionController::class, 'valider'])->name('espace-admin.inscriptions.valider');
        Route::patch('inscriptions/{inscription}/refuser', [AdminInscriptionController::class, 'refuser'])->name('espace-admin.inscriptions.refuser');
        Route::get('factures', [AdminFactureController::class, 'index'])->name('espace-admin.factures.index');
        Route::post('factures', [AdminFactureController::class, 'store'])->name('espace-admin.factures.store');
        Route::get('factures/{facture}', [AdminFactureController::class, 'show'])->name('espace-admin.factures.show');
        Route::post('factures/{facture}/paiements', [AdminFactureController::class, 'storePaiement'])->name('espace-admin.factures.paiements.store');
        Route::patch('paiements/{paiement}', [AdminFactureController::class, 'updatePaiement'])->name('espace-admin.paiements.update');
        Route::get('contrats', [AdminContratController::class, 'index'])->name('espace-admin.contrats.index');
        Route::post('contrats', [AdminContratController::class, 'store'])->name('espace-admin.contrats.store');
        Route::get('contrats/{contrat}', [AdminContratController::class, 'show'])->name('espace-admin.contrats.show');
        Route::get('contrats/{contrat}/pdf', [AdminContratController::class, 'pdf'])->name('espace-admin.contrats.pdf');
        Route::patch('contrats/{contrat}', [AdminContratController::class, 'update'])->name('espace-admin.contrats.update');
        Route::post('modeles-contrat', [AdminContratController::class, 'storeModele'])->name('espace-admin.modeles-contrat.store');
        Route::patch('modeles-contrat/{modeleContrat}', [AdminContratController::class, 'updateModele'])->name('espace-admin.modeles-contrat.update');
        Route::post('clauses', [AdminContratController::class, 'storeClause'])->name('espace-admin.clauses.store');
        Route::delete('clauses/{clause}', [AdminContratController::class, 'destroyClause'])->name('espace-admin.clauses.destroy');
        Route::get('abonnements', [AdminAbonnementController::class, 'index'])->name('espace-admin.abonnements.index');
        Route::post('abonnements', [AdminAbonnementController::class, 'store'])->name('espace-admin.abonnements.store');
        Route::patch('abonnements/{abonnement}', [AdminAbonnementController::class, 'update'])->name('espace-admin.abonnements.update');
        Route::post('formules', [AdminAbonnementController::class, 'storeFormule'])->name('espace-admin.formules.store');
        Route::patch('formules/{formule}', [AdminAbonnementController::class, 'updateFormule'])->name('espace-admin.formules.update');
        Route::delete('formules/{formule}', [AdminAbonnementController::class, 'destroyFormule'])->name('espace-admin.formules.destroy');
        Route::get('apprenants', [AdminApprenantController::class, 'index'])->name('espace-admin.apprenants.index');
        Route::get('apprenants/{apprenant}', [AdminApprenantController::class, 'show'])->name('espace-admin.apprenants.show');
        Route::get('apprenants/{apprenant}/cv', [AdminApprenantController::class, 'cv'])->name('espace-admin.apprenants.cv');
        Route::delete('apprenants/{apprenant}', [AdminApprenantController::class, 'destroy'])->name('espace-admin.apprenants.destroy');
        Route::get('candidats-externes/{candidatureSpontanee}/cv', [AdminCandidatExterneController::class, 'cv'])->name('espace-admin.candidats-externes.cv');
        Route::delete('candidats-externes/{candidatureSpontanee}', [AdminCandidatExterneController::class, 'destroy'])->name('espace-admin.candidats-externes.destroy');
        Route::get('statistiques', AdminStatistiqueController::class)->name('espace-admin.statistiques');
    });

/*
| Online payment by the payer (client, entreprise or apprenant): initiation,
| the simulation provider screen, and the provider's signed webhook.
*/
Route::middleware(['auth', 'verified'])->prefix('paiements')->group(function () {
    Route::post('factures/{facture}', [PaiementEnLigneController::class, 'initier'])->name('paiements.initier');
    Route::get('simulation/{paiement}', [PaiementEnLigneController::class, 'simulation'])->name('paiements.simulation');
    Route::post('simulation/{paiement}', [PaiementEnLigneController::class, 'decision'])->name('paiements.simulation.decision');
});

Route::post('webhooks/paiements', PaiementWebhookController::class)->name('paiements.webhook');
