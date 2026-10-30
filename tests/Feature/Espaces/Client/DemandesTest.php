<?php

use App\Models\Client;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\Service;

test('the demandes index lists only the authenticated client own demandes', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();

    $mienne = Demande::factory()->for($client)->create();
    Demande::factory()->for($autre)->create();

    $response = $this->actingAs($client->user)->get(route('espace-client.demandes.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/demandes-index')
        ->has('demandes', 1)
        ->where('demandes.0.id', $mienne->id));
});

test('the demande creation form exposes published services and formules only', function () {
    $client = Client::factory()->create();
    Service::factory()->create(['statut' => 'PUBLIE']);
    Service::factory()->create(['statut' => 'BROUILLON']);
    Formule::factory()->create(['statut' => 'PUBLIEE']);
    Formule::factory()->create(['statut' => 'BROUILLON']);

    $response = $this->actingAs($client->user)->get(route('espace-client.demandes.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/demandes-create')
        ->has('services', 1)
        ->has('formules', 1));
});

test('a client can submit a demande for a service', function () {
    $client = Client::factory()->create();
    $service = Service::factory()->create(['statut' => 'PUBLIE']);

    $response = $this->actingAs($client->user)->post(route('espace-client.demandes.store'), [
        'type' => 'service',
        'service_id' => $service->id,
        'description' => 'J\'ai besoin d\'un accompagnement sur ce service.',
    ]);

    $demande = Demande::where('client_id', $client->id)->first();

    $response->assertRedirect(route('espace-client.demandes.show', $demande));
    expect($demande)->not->toBeNull();
    expect($demande->service_id)->toBe($service->id);
    expect($demande->formule_id)->toBeNull();
    expect($demande->statut)->toBe('EN_ETUDE');
});

test('a client can submit a demande for a formule', function () {
    $client = Client::factory()->create();
    $formule = Formule::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($client->user)->post(route('espace-client.demandes.store'), [
        'type' => 'formule',
        'formule_id' => $formule->id,
        'description' => 'Je souhaite souscrire à cette formule.',
    ])->assertSessionHasNoErrors();

    $demande = Demande::where('client_id', $client->id)->first();

    expect($demande->formule_id)->toBe($formule->id);
    expect($demande->service_id)->toBeNull();
});

test('submitting a demande without the required fields fails validation', function () {
    $client = Client::factory()->create();

    $response = $this->actingAs($client->user)->post(route('espace-client.demandes.store'), [
        'type' => 'service',
        'description' => '',
    ]);

    $response->assertSessionHasErrors(['service_id', 'description']);
    expect(Demande::count())->toBe(0);
});

test('a client can view their own demande with its suivi', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->for($client)->create(['statut' => 'EN_COURS']);

    $response = $this->actingAs($client->user)->get(route('espace-client.demandes.show', $demande));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/demandes-show')
        ->where('demande.id', $demande->id)
        ->where('suivi.etapeActuelle', 4)
        ->where('suivi.refusee', false));
});

test('a client cannot view another client demande', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();
    $demande = Demande::factory()->for($autre)->create();

    $this->actingAs($client->user)->get(route('espace-client.demandes.show', $demande))->assertForbidden();
});
