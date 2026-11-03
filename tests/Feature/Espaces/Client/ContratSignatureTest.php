<?php

use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('generating a contrat drafts it from the active modele and the clauses', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'PAYEE', 'montant_negocie' => 700000]);
    ModeleContrat::create([
        'nom' => 'Prestation', 'type' => 'PRESTATION_SERVICE', 'statut' => 'ACTIF',
        'contenu' => 'Contrat entre DAG et {{client}} pour {{service}}, au montant de {{montant}} FCFA.',
    ]);
    Clause::create(['titre' => 'Confidentialité', 'contenu' => 'Les parties restent discrètes.', 'ordre' => 1]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.store'), ['demande_id' => $demande->id])
        ->assertRedirect();

    $contrat = Contrat::firstOrFail();
    expect($contrat->statut)->toBe('BROUILLON');
    expect($contrat->contenu)->toContain('Aïcha N.');
    expect($contrat->contenu)->toContain('700 000 FCFA');
    expect($contrat->contenu)->not->toContain('{{');
    expect($contrat->contenu)->toContain("1. Confidentialité\nLes parties restent discrètes.");
});

test('a draft contrat is hidden from the client until DAG sends it', function () {
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'BROUILLON']);

    $this->actingAs($client->user)
        ->get(route('espace-client.contrats'))
        ->assertInertia(fn ($page) => $page->has('contrats', 0));

    $this->actingAs($client->user)
        ->get(route('espace-client.contrats.show', $contrat))
        ->assertNotFound();
});

test('the client can read a sent contrat and sign it by importing their signature', function () {
    Storage::fake('local');
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE', 'contenu' => "1. Objet\nTexte."]);

    $this->actingAs($client->user)
        ->get(route('espace-client.contrats.show', $contrat))
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/contrats-show')
            ->where('contrat.peutSigner', true));

    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Aïcha Ngono',
            'accepte' => true,
        ])
        ->assertRedirect();

    $contrat->refresh();
    expect($contrat->statut)->toBe('SIGNE');
    expect($contrat->signataire_nom)->toBe('Aïcha Ngono');
    expect($contrat->date_signature)->not->toBeNull();
    expect($contrat->ip_signature)->not->toBeNull();
    Storage::disk('local')->assertExists($contrat->photo_signature);

    $this->actingAs($client->user)
        ->get(route('espace-client.contrats.pdf', $contrat))
        ->assertOk();
});

test('signing requires an image, the accepted terms and a contrat open to signature', function () {
    Storage::fake('local');
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE']);

    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $contrat), ['nom' => 'Aïcha', 'accepte' => true])
        ->assertSessionHasErrors('signature');

    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('s.png'),
            'nom' => 'Aïcha',
        ])
        ->assertSessionHasErrors('accepte');

    expect($contrat->fresh()->statut)->toBe('ENVOYE');

    $deja = Contrat::factory()->signe()->create(['client_id' => $client->id]);
    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $deja), [
            'signature' => UploadedFile::fake()->image('s.png'),
            'nom' => 'Aïcha',
            'accepte' => true,
        ])
        ->assertStatus(409);
});

test('a client cannot read, sign or download another client contrat', function () {
    Storage::fake('local');
    $client = Client::factory()->create();
    $autre = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $autre->id, 'statut' => 'ENVOYE']);

    $this->actingAs($client->user)->get(route('espace-client.contrats.show', $contrat))->assertNotFound();
    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('s.png'),
            'nom' => 'X',
            'accepte' => true,
        ])
        ->assertNotFound();
    $this->actingAs($client->user)->get(route('espace-client.contrats.pdf', $contrat))->assertNotFound();

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
});

test('the admin can send a draft contrat to the client', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $contrat = Contrat::factory()->create(['statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ENVOYE'])
        ->assertRedirect();

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
});
