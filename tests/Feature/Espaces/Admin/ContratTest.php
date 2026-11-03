<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\Client;
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
    $demande = Demande::factory()->create(['statut' => 'PAYEE', 'montant_negocie' => 85000]);
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

test('only paid demandes without a contrat are listed to contract, with the client details', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.', 'telephone' => '+237 690000000']);
    $payee = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'PAYEE', 'montant_negocie' => 700000]);
    Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    $dejaContractee = Demande::factory()->create(['statut' => 'PAYEE']);
    Contrat::factory()->create(['demande_id' => $dejaContractee->id]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertInertia(fn ($page) => $page
            ->has('demandesAContractualiser', 1)
            ->where('demandesAContractualiser.0.id', $payee->id)
            ->where('demandesAContractualiser.0.client.nom', 'Aïcha N.')
            ->where('demandesAContractualiser.0.client.email', $client->user->email)
            ->where('demandesAContractualiser.0.client.telephone', '+237 690000000')
            ->where('demandesAContractualiser.0.montant', '700000.00'));
});
