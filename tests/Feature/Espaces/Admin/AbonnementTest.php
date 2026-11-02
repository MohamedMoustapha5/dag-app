<?php

use App\Enums\MembreEquipeRole;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;
use App\Models\MembreEquipe;

test('a controle de gestion can create an abonnement and then activate it', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $formule = Formule::factory()->create(['nom' => 'Pack Croissance', 'statut' => 'PUBLIEE']);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-abonnements')
            ->has('clients', 1)
            ->has('formules', 1));

    $this->actingAs($membre->user)->post(route('espace-admin.abonnements.store'), [
        'client_id' => $client->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 45000,
        'date_souscription' => now()->toDateString(),
    ])->assertRedirect();

    $abonnement = Abonnement::where('client_id', $client->id)->firstOrFail();
    expect($abonnement->statut)->toBe('BROUILLON');

    $this->actingAs($membre->user)
        ->patch(route('espace-admin.abonnements.update', $abonnement), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($abonnement->fresh()->statut)->toBe('ACTIF');
});

test('abonnements index lists the subscriber name for both client and entreprise records', function () {
    $membre = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $formule = Formule::factory()->create();
    Abonnement::create([
        'client_id' => $client->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 45000,
        'date_souscription' => now()->toDateString(),
        'statut' => 'ACTIF',
    ]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('abonnements', 1)
            ->where('abonnements.0.client', 'Aïcha N.'));
});

test('a role without access to abonnements cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.abonnements.index'))
        ->assertForbidden();
});
