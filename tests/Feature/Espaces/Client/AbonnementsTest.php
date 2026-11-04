<?php

use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;

function abonnementPour(Client $client, array $attributs = []): Abonnement
{
    return Abonnement::query()->create([
        'client_id' => $client->id,
        'formule_id' => Formule::factory()->create(['nom' => 'Croissance'])->id,
        'montant_negocie' => 180000,
        'date_souscription' => now()->subMonths(2),
        'cycle_debut' => now()->subDays(20),
        'echeance' => now()->addDays(10),
        'statut' => 'ACTIF',
        ...$attributs,
    ]);
}

test('a client sees every subscription with its progression and state', function () {
    $client = Client::factory()->create();
    abonnementPour($client);
    abonnementPour($client, ['echeance' => now()->addDays(90), 'cycle_debut' => now()->subDays(10)]);
    abonnementPour($client, ['statut' => 'TERMINE', 'echeance' => now()->subMonth()]);
    abonnementPour($client, ['statut' => 'BROUILLON']);
    abonnementPour(Client::factory()->create());

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/abonnements-index')
            ->has('abonnements', 3)
            ->where('resume.actifs', 2)
            ->where('resume.aRenouveler', 2)
            ->where('abonnements.0.etat', 'bientot')
            ->where('abonnements.0.peutRenouveler', true)
            ->where('abonnements.1.etat', 'actif')
            ->where('abonnements.1.peutRenouveler', false)
            ->where('abonnements.2.etat', 'termine')
            ->where('abonnements.2.progression', 100));
});

test('an expired subscription that is still marked active is flagged as expired', function () {
    $client = Client::factory()->create();
    abonnementPour($client, ['echeance' => now()->subDays(3)]);

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertInertia(fn ($page) => $page
            ->where('abonnements.0.etat', 'expire')
            ->where('abonnements.0.peutRenouveler', true)
            ->where('resume.actifs', 0));
});

test('renewing creates a formule request for the team and is not duplicated', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client);

    $reponse = $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement));

    $demande = $client->demandes()->firstOrFail();
    $reponse->assertRedirect(route('espace-client.demandes.show', $demande));
    expect($demande->formule_id)->toBe($abonnement->formule_id)
        ->and($demande->statut)->toBe('EN_ETUDE')
        ->and($demande->description)->toStartWith('Renouvellement');

    $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertRedirect(route('espace-client.demandes.show', $demande));

    expect($client->demandes()->count())->toBe(1);
});

test('a subscription far from its end cannot be renewed yet', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client, ['echeance' => now()->addDays(120)]);

    $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertStatus(409);

    expect($client->demandes()->count())->toBe(0);
});

test('a client cannot renew the subscription of another client', function () {
    $abonnement = abonnementPour(Client::factory()->create());
    $autre = Client::factory()->create();

    $this->actingAs($autre->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertNotFound();
});
