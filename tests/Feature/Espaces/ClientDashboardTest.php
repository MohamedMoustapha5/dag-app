<?php

use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Formule;

test('the client dashboard exposes stats computed from the authenticated client own records', function () {
    $client = Client::factory()->create();

    Demande::factory()->for($client)->create(['statut' => 'EN_COURS']);
    Demande::factory()->for($client)->create(['statut' => 'TERMINEE']);
    Demande::factory()->for($client)->create(['statut' => 'TERMINEE']);
    Facture::factory()->for($client)->create(['montant' => 75000]);

    Abonnement::query()->create([
        'client_id' => $client->id,
        'formule_id' => Formule::factory()->create()->id,
        'montant_negocie' => 50000,
        'date_souscription' => now(),
        'statut' => 'ACTIF',
    ]);

    $response = $this->actingAs($client->user)->get(route('espace-client.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client-dashboard')
        ->where('stats.demandesEnCours', 1)
        ->where('stats.facturesEnAttenteNombre', 1)
        ->where('stats.contratsASigner', 0)
        ->where('formule.nom', fn ($nom) => $nom !== null)
        ->has('activiteRecente', 3));
});
