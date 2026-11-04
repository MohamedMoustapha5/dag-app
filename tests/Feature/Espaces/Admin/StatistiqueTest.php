<?php

use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\MembreEquipe;
use App\Support\DroitsMatrix;

test('a controle de gestion can see the statistiques dashboard', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    Apprenant::factory()->count(3)->create();
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Facture::factory()->create(['montant' => 100000, 'montant_paye' => 100000, 'statut' => 'PAYEE']);
    Facture::factory()->create(['montant' => 50000, 'montant_paye' => 0, 'statut' => 'EN_ATTENTE']);

    $response = $this->actingAs($membre, 'admin')->get(route('espace-admin.statistiques'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-statistiques')
        ->where('activite.apprenants', null)
        ->where('demandesParStatut.EN_ETUDE', 2)
        ->where('finances.factureEncaissee', 100000)
        ->where('finances.factureEnAttente', 50000)
        ->where('finances.facturesImpayeesCount', 1));
});

test('statistics only show the domains the viewer may see', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    Apprenant::factory()->count(2)->create();
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Facture::factory()->create(['montant' => 100000, 'montant_paye' => 100000, 'statut' => 'PAYEE']);

    $this->actingAs($juriste, 'admin')->get(route('espace-admin.statistiques'))
        ->assertInertia(fn ($page) => $page
            ->where('activite.apprenants', 2)
            ->where('demandesParStatut.EN_ETUDE', 1)
            ->where('finances', null));
});

test('a role without access to statistiques cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.statistiques'))
        ->assertForbidden();
});

test('the directrice sees the activity journal, newest first, with author filter and search', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create(['name' => 'Marie D.']);
    $juriste = MembreEquipe::factory()->juriste()->create(['name' => 'Fatou M.']);
    ActivityLog::record($juriste, 'a validé le contrat', null, 'DAG-CTR-0001.');
    ActivityLog::record($promotrice, 'a envoyé un devis', null, 'DAG-DEM-0002 : 700 000 FCFA.');
    ActivityLog::record(null, 'paiement en ligne confirmé', null, 'DAG-F-0003.');

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'journal']))
        ->assertInertia(fn ($page) => $page
            ->where('onglet', 'journal')
            ->where('onglets', ['indicateurs', 'journal', 'notifications', 'documents'])
            ->where('journal.total', 3)
            ->where('journal.entrees.0.action', 'paiement en ligne confirmé')
            ->where('journal.entrees.0.auteur', null));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'journal', 'auteur' => $juriste->id]))
        ->assertInertia(fn ($page) => $page->where('journal.total', 1)->where('journal.entrees.0.auteur', 'Fatou M.'));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'journal', 'q' => 'devis']))
        ->assertInertia(fn ($page) => $page->where('journal.total', 1));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'journal', 'auteur' => 'systeme']))
        ->assertInertia(fn ($page) => $page->where('journal.total', 1));
});

test('only the directrice can open the journal: other roles fall back to the indicators', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    ActivityLog::record(null, 'quelque chose', null, null);

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'journal']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onglet', 'indicateurs')
            ->where('journal', null)
            ->where('onglets', ['indicateurs', 'documents']));
});

test('admin actions are recorded in the journal', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 120000])
        ->assertRedirect();

    $entree = ActivityLog::firstOrFail();
    expect($entree->action)->toBe('a envoyé un devis');
    expect($entree->membre_equipe_id)->toBe($promotrice->id);
    expect($entree->description)->toContain($demande->reference);
});

test('the documents tab gathers signed contrats, attestations and cvs the role may download', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    Contrat::factory()->signe()->create();
    Contrat::factory()->create(['statut' => 'ENVOYE']);
    $apprenant = Apprenant::factory()->create(['cv_path' => 'cv/a.pdf']);
    Attestation::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => Formation::factory()->create()->id,
        'date_emission' => now()->toDateString(),
    ]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'documents']))
        ->assertInertia(fn ($page) => $page
            ->where('documents.total', 3)
            ->where('documents.types.0.value', 'contrats')
            ->where('documents.types.0.total', 1));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'documents', 'type' => 'attestations']))
        ->assertInertia(fn ($page) => $page->where('documents.total', 1));
});

test('the documents tab is limited to the families the role may download', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    Contrat::factory()->signe()->create();
    DroitsMatrix::definir(MembreEquipeRole::CommunicationMarketing, Donnee::ContratsClauses, NiveauDroit::Aucun);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'documents']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onglet', 'indicateurs')
            ->where('documents', null));
});
