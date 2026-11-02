<?php

use App\Models\Client;
use App\Models\Contrat;
use App\Models\Facture;

test('the documents page lists only the authenticated client own contrats and factures', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();

    $monContrat = Contrat::factory()->for($client)->signe()->create();
    $maFacture = Facture::factory()->for($client)->payee()->create();
    Contrat::factory()->for($autre)->create();
    Facture::factory()->for($autre)->create();

    $response = $this->actingAs($client->user)->get(route('espace-client.documents'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/documents')
        ->has('contrats', 1)
        ->where('contrats.0.id', $monContrat->id)
        ->where('contrats.0.fichierPdf', $monContrat->fichier_pdf)
        ->has('factures', 1)
        ->where('factures.0.id', $maFacture->id));
});

test('a guest cannot reach the documents page', function () {
    $this->get(route('espace-client.documents'))->assertRedirect(route('login'));
});

test('a client can download the pdf of their paid facture only', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();
    $payee = Facture::factory()->payee()->create(['client_id' => $client->id]);
    $enAttente = Facture::factory()->create(['client_id' => $client->id]);
    $autrePayee = Facture::factory()->payee()->create(['client_id' => $autre->id]);

    $response = $this->actingAs($client->user)
        ->get(route('espace-client.factures.pdf', $payee))
        ->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->actingAs($client->user)
        ->get(route('espace-client.factures.pdf', $enAttente))
        ->assertForbidden();

    $this->actingAs($client->user)
        ->get(route('espace-client.factures.pdf', $autrePayee))
        ->assertNotFound();
});
