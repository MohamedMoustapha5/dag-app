<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;

test('the apprenant inscriptions page lists own inscriptions with their facture', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create(['ville' => 'Douala']);

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'montant_negocie' => 75000,
        'statut' => 'VALIDEE',
    ]);

    $facture = Facture::factory()->create([
        'type' => 'FORMATION',
        'client_id' => null,
        'apprenant_id' => $apprenant->id,
        'inscription_id' => $inscription->id,
        'montant' => 75000,
        'montant_paye' => 75000,
        'statut' => 'PAYEE',
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.inscriptions'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/inscriptions-index')
        ->has('inscriptions', 1)
        ->where('inscriptions.0.formation', 'Community Management')
        ->where('inscriptions.0.statut', 'VALIDEE')
        ->where('inscriptions.0.facture.reference', $facture->reference)
        ->where('inscriptions.0.facture.statut', 'PAYEE'));
});

test('an inscription without a facture yet omits it', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();

    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.inscriptions'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/inscriptions-index')
        ->where('inscriptions.0.facture', null));
});

test('a client cannot reach the apprenant inscriptions page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.inscriptions'))->assertForbidden();
});
