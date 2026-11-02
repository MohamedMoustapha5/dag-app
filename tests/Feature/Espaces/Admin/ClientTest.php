<?php

use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Demande;
use App\Models\DroitRole;
use App\Models\MembreEquipe;
use Illuminate\Support\Facades\Cache;

test('a membre with voir can list the client accounts', function () {
    $membre = MembreEquipe::factory()->juriste()->create();
    $client = Client::factory()->create(['nom' => 'Entreprise ACME']);
    Demande::factory()->create(['client_id' => $client->id]);

    $response = $this->actingAs($membre, 'admin')->get(route('espace-admin.clients.index'));

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

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.clients.destroy', $client))
        ->assertRedirect();

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseHas('demandes', ['id' => $demande->id, 'client_id' => null]);
});

test('a role without supprimer cannot delete a client account', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $client = Client::factory()->create();

    $this->actingAs($membre, 'admin')
        ->delete(route('espace-admin.clients.destroy', $client))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $client->id]);
});

test('a role without access to demandes clients cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.clients.index'))
        ->assertForbidden();
});

test('the comptes page also lists the apprenant accounts for a role allowed to see dossiers', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create(['ville' => 'Yaoundé']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.clients.index'))
        ->assertInertia(fn ($page) => $page
            ->has('apprenants', 1)
            ->where('apprenants.0.id', $apprenant->id)
            ->where('apprenants.0.ville', 'Yaoundé'));
});

test('the comptes page hides apprenant accounts from a role without access to dossiers', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    Apprenant::factory()->create();

    DroitRole::create([
        'role' => MembreEquipeRole::CommunicationMarketing->value,
        'donnee' => Donnee::DossiersApprenants->value,
        'niveau' => NiveauDroit::Aucun->value,
    ]);
    Cache::forget('droits_roles');

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.clients.index'))
        ->assertInertia(fn ($page) => $page->where('apprenants', null));
});
