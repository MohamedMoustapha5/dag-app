<?php

use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Service;
use App\Models\TicketSupport;

test('the topbar search finds the client records and the published catalogue, each opening a page of the espace', function () {
    $client = Client::factory()->create();
    $service = Service::factory()->create(['nom' => 'Site vitrine', 'statut' => 'PUBLIE']);
    $demande = Demande::factory()->create(['client_id' => $client->id, 'service_id' => $service->id]);
    $ticket = TicketSupport::factory()->create(['user_id' => $client->user_id, 'sujet' => 'Question sur mon site vitrine']);

    $response = $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => 'vitrine']))
        ->assertOk();

    $resultats = collect($response->json('resultats'))->keyBy('type');

    expect($resultats->keys()->all())->toContain('Demande', 'Support', 'Service');
    expect($resultats['Demande']['url'])->toBe(route('espace-client.demandes.show', $demande, absolute: false));
    expect($resultats['Support']['url'])->toBe(route('espace-client.support.show', $ticket, absolute: false));
    expect($resultats['Service']['url'])->toBe(route('espace-client.demandes.create', ['service' => $service->id], absolute: false));
});

test('the search finds factures and sent contrats by reference', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE']);
    Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'BROUILLON', 'reference' => 'DAG-CTR-BROUILLON-1']);

    $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => $facture->reference]))
        ->assertJsonPath('resultats.0.type', 'Facture');

    $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => $contrat->reference]))
        ->assertJsonPath('resultats.0.url', route('espace-client.contrats.show', $contrat, absolute: false));

    $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => 'DAG-CTR-BROUILLON']))
        ->assertJsonCount(0, 'resultats');
});

test('the search never returns another client records nor unpublished services', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();
    Demande::factory()->create(['client_id' => $autre->id, 'description' => 'Projet confidentiel Zephyr']);
    Facture::factory()->create(['client_id' => $autre->id, 'reference' => 'DAG-F-ZEPHYR-1']);
    TicketSupport::factory()->create(['user_id' => $autre->user_id, 'sujet' => 'Zephyr']);
    Service::factory()->create(['nom' => 'Zephyr en préparation', 'statut' => 'BROUILLON']);

    $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => 'Zephyr']))
        ->assertOk()
        ->assertJsonCount(0, 'resultats');
});

test('a term shorter than two characters returns nothing', function () {
    $client = Client::factory()->create();
    Service::factory()->create(['nom' => 'Audit', 'statut' => 'PUBLIE']);

    $this->actingAs($client->user)
        ->getJson(route('espace-client.recherche', ['q' => 'a']))
        ->assertJsonCount(0, 'resultats');
});

test('only clients can use the client espace search', function () {
    $this->getJson(route('espace-client.recherche', ['q' => 'site']))->assertUnauthorized();
});
