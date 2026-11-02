<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use Illuminate\Support\Facades\Route;

test('the apprenant factures page lists own factures with stats', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
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
        'moyen_paiement' => 'MTN_MOMO',
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.factures'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/factures-index')
        ->has('factures', 1)
        ->where('factures.0.reference', $facture->reference)
        ->where('factures.0.objet', 'Formation — Community Management')
        ->where('factures.0.statut', 'PAYEE')
        ->where('stats.regleCetteAnnee', '75000'));
});

test('a client cannot reach the apprenant factures page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.factures'))->assertForbidden();
});

test('an apprenant can download the pdf of their own paid facture only', function () {
    $apprenant = Apprenant::factory()->create();
    $autre = Apprenant::factory()->create();
    $payee = Facture::factory()->payee()->create(['type' => 'FORMATION', 'client_id' => null, 'apprenant_id' => $apprenant->id]);
    $enAttente = Facture::factory()->create(['type' => 'FORMATION', 'client_id' => null, 'apprenant_id' => $apprenant->id]);

    $response = $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.factures.pdf', $payee))
        ->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.factures.pdf', $enAttente))
        ->assertForbidden();

    $this->actingAs($autre->user)
        ->get(route('espace-apprenant.factures.pdf', $payee))
        ->assertNotFound();
});

test('the admin has no pdf download for factures', function () {
    expect(Route::has('espace-admin.factures.pdf'))->toBeFalse();
});
