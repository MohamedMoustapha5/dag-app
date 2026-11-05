<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;

test('a promotrice can create a modele de contrat and activate it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.modeles-contrat.store'), [
        'nom' => 'Prestation standard',
        'type' => 'PRESTATION_SERVICE',
        'contenu' => 'Le présent contrat a pour objet...',
    ])->assertRedirect();

    $modele = ModeleContrat::where('nom', 'Prestation standard')->firstOrFail();
    expect($modele->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.modeles-contrat.update', $modele), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($modele->fresh()->statut)->toBe('ACTIF');
});

test('a promotrice can add and delete a clause', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.clauses.store'), [
        'titre' => 'Confidentialité',
        'contenu' => 'Les parties s\'engagent à garder confidentielles...',
        'ordre' => 1,
    ])->assertRedirect();

    $clause = Clause::where('titre', 'Confidentialité')->firstOrFail();

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.clauses.destroy', $clause))
        ->assertRedirect();

    $this->assertDatabaseMissing('clauses', ['id' => $clause->id]);
});

test('a promotrice can generate a contrat for a demande and move it through signature', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 85000]);
    $modele = ModeleContrat::create([
        'nom' => 'Prestation standard',
        'type' => 'PRESTATION_SERVICE',
        'contenu' => 'Contenu du modèle.',
        'statut' => 'ACTIF',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-contrats')
            ->has('demandesAContractualiser', 1)
            ->has('modeles', 1));

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.contrats.store'), [
        'demande_id' => $demande->id,
        'modele_contrat_id' => $modele->id,
    ])->assertRedirect();

    $contrat = Contrat::where('demande_id', $demande->id)->firstOrFail();
    expect($contrat->statut)->toBe('BROUILLON');
    expect($contrat->contenu)->toBe('Contenu du modèle.');

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.show', $contrat))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-contrat-show')
            ->where('contrat.reference', $contrat->reference));

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ENVOYE'])
        ->assertRedirect();

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
});

test('a demande already under contract cannot be contracted twice', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    Contrat::factory()->create(['demande_id' => $demande->id, 'client_id' => null]);

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.contrats.store'), [
        'demande_id' => $demande->id,
    ])->assertStatus(409);
});

test('communication marketing has read-only access to contrats clauses', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('peutCreer', false));

    $this->actingAs($membre, 'admin')->post(route('espace-admin.modeles-contrat.store'), [
        'nom' => 'Test',
        'type' => 'PRESTATION_SERVICE',
        'contenu' => 'Test',
    ])->assertForbidden();
});

test('an admin can download the pdf of a signed contrat', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $contrat = Contrat::factory()->signe()->create(['signataire_nom' => 'Aïcha N.']);

    $response = $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.pdf', $contrat))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain($contrat->reference);
});

test('a contrat that is not signed yet cannot be downloaded as pdf', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $contrat = Contrat::factory()->create(['statut' => 'ENVOYE']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.pdf', $contrat))
        ->assertStatus(409);
});

test('a role without access to contrats cannot download a signed contrat pdf', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);
    $contrat = Contrat::factory()->signe()->create();

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.contrats.pdf', $contrat))
        ->assertForbidden();
});

test('a promotrice can edit a clause and the change only applies to contracts drafted afterwards', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $clause = Clause::create(['titre' => 'Confidentialité', 'contenu' => 'Texte initial.', 'ordre' => 1]);
    $ancienContrat = Contrat::factory()->create(['contenu' => "1. Confidentialité\nTexte initial."]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.clauses.update', $clause), [
            'titre' => 'Confidentialité renforcée',
            'contenu' => 'Texte révisé.',
            'ordre' => 3,
        ])
        ->assertRedirect();

    $clause->refresh();
    expect($clause->titre)->toBe('Confidentialité renforcée');
    expect($clause->contenu)->toBe('Texte révisé.');
    expect($clause->ordre)->toBe(3);
    expect($ancienContrat->fresh()->contenu)->toContain('Texte initial.');

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertInertia(fn ($page) => $page->where('clauses.0.contenu', 'Texte révisé.'));
});

test('a clause needs a title and a content, and a read-only role cannot edit it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $clause = Clause::create(['titre' => 'Durée', 'contenu' => 'Texte.', 'ordre' => 0]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.clauses.update', $clause), ['titre' => '', 'contenu' => ''])
        ->assertSessionHasErrors(['titre', 'contenu']);

    $this->actingAs($marketing, 'admin')
        ->patch(route('espace-admin.clauses.update', $clause), ['titre' => 'Autre', 'contenu' => 'Autre.'])
        ->assertForbidden();

    expect($clause->fresh()->titre)->toBe('Durée');
});

test('staff can never mark a contrat as signed by hand', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $contrat = Contrat::factory()->create(['statut' => 'ENVOYE', 'date_signature' => null]);

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'SIGNE'])
        ->assertSessionHasErrors(['statut' => 'Seule la signature du client fait passer un contrat à « Signé ».']);

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
    expect($contrat->fresh()->estSigne())->toBeFalse();
});

test('a signed contrat cannot be sent back to signature, which would overwrite its evidence', function (string $statut) {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $contrat = Contrat::factory()->create(['statut' => 'SIGNE', 'date_signature' => now(), 'signataire_nom' => 'Aïcha N.']);

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => $statut])
        ->assertSessionHasErrors('statut');

    expect($contrat->fresh()->statut)->toBe('SIGNE');
})->with(['ENVOYE', 'BROUILLON']);

test('a signed contrat can still move on to actif then terminé', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $contrat = Contrat::factory()->create(['statut' => 'SIGNE', 'date_signature' => now()]);

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF'])
        ->assertSessionHasNoErrors();
    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'TERMINE'])
        ->assertSessionHasNoErrors();

    expect($contrat->fresh()->statut)->toBe('TERMINE');
});
