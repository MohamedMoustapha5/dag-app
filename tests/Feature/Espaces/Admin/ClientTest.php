<?php

use App\Enums\MembreEquipeRole;
use App\Models\Client;
use App\Models\Demande;
use App\Models\MembreEquipe;

test('a membre with voir can list the client accounts', function () {
    $membre = MembreEquipe::factory()->juriste()->create();
    $client = Client::factory()->create(['nom' => 'Entreprise ACME']);
    Demande::factory()->create(['client_id' => $client->id]);

    $response = $this->actingAs($membre->user)->get(route('espace-admin.clients.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-clients')
        ->where('clients.0.nom', 'Entreprise ACME')
        ->where('clients.0.demandesCount', 1)
        ->where('actions.supprimer', false));
});

test('a promotrice can delete a client account, which preserves its historical demandes', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id]);
    $userId = $client->user_id;

    $this->actingAs($promotrice->user)
        ->delete(route('espace-admin.clients.destroy', $client))
        ->assertRedirect();

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseHas('demandes', ['id' => $demande->id, 'client_id' => null]);
});

test('a role without supprimer cannot delete a client account', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $client = Client::factory()->create();

    $this->actingAs($membre->user)
        ->delete(route('espace-admin.clients.destroy', $client))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $client->id]);
});

test('a role without access to demandes clients cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.clients.index'))
        ->assertForbidden();
});
