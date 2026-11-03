<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;

test('a client starts an Orange Money payment and is sent to the simulation screen', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 100000]);

    $response = $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '+237 690 00 00 00']);

    $paiement = Paiement::firstOrFail();
    $response->assertRedirect(route('paiements.simulation', $paiement));

    expect($paiement->statut)->toBe('EN_ATTENTE');
    expect($paiement->moyen)->toBe('OM');
    expect((float) $paiement->montant)->toBe(100000.0);
    expect($paiement->reference_transaction)->toStartWith('SIM-');
    expect($facture->fresh()->statut)->toBe('EN_ATTENTE');
});

test('confirming the payment settles the facture and moves the demande to paid', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'DEVIS_ENVOYE']);
    $facture = Facture::factory()->create(['client_id' => $client->id, 'demande_id' => $demande->id, 'montant' => 80000]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'MTN_MOMO', 'telephone' => '670000000']);
    $paiement = Paiement::firstOrFail();

    $this->actingAs($client->user)
        ->post(route('paiements.simulation.decision', $paiement), ['resultat' => 'succes'])
        ->assertRedirect(route('espace-client.factures'));

    $facture->refresh();
    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect($facture->statut)->toBe('PAYEE');
    expect((float) $facture->montant_paye)->toBe(80000.0);
    expect($facture->moyen_paiement)->toBe('MTN_MOMO');
    expect($demande->fresh()->statut)->toBe('PAYEE');
});

test('a failed payment leaves the facture unpaid and can be retried', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 50000]);

    $this->actingAs($client->user)->post(route('paiements.initier', $facture), ['moyen' => 'CARTE']);
    $paiement = Paiement::firstOrFail();

    $this->actingAs($client->user)
        ->post(route('paiements.simulation.decision', $paiement), ['resultat' => 'echec']);

    expect($paiement->fresh()->statut)->toBe('ECHEC');
    expect($facture->fresh()->statut)->toBe('EN_ATTENTE');

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertRedirect();

    expect(Paiement::count())->toBe(2);
});

test('a second payment cannot start while one is awaiting confirmation', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)->post(route('paiements.initier', $facture), ['moyen' => 'CARTE']);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::count())->toBe(1);
});

test('a mobile money payment requires a valid Cameroonian number', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '12345'])
        ->assertSessionHasErrors('telephone');

    expect(Paiement::count())->toBe(0);
});

test('an already paid facture cannot be paid again', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->payee()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');
});

test('nobody can pay or confirm a payment on a facture that is not theirs', function () {
    $client = Client::factory()->create();
    $autre = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $autre->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertNotFound();

    $this->actingAs($autre->user)->post(route('paiements.initier', $facture), ['moyen' => 'CARTE']);
    $paiement = Paiement::firstOrFail();

    $this->actingAs($client->user)
        ->post(route('paiements.simulation.decision', $paiement), ['resultat' => 'succes'])
        ->assertNotFound();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
});

test('an apprenant can pay their own facture and returns to their space', function () {
    $apprenant = Apprenant::factory()->create();
    $facture = Facture::factory()->create(['type' => 'FORMATION', 'client_id' => null, 'apprenant_id' => $apprenant->id, 'montant' => 75000]);

    $this->actingAs($apprenant->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '655555555'])
        ->assertRedirect();

    $paiement = Paiement::firstOrFail();

    $this->actingAs($apprenant->user)
        ->post(route('paiements.simulation.decision', $paiement), ['resultat' => 'succes'])
        ->assertRedirect(route('espace-apprenant.factures'));

    expect($facture->fresh()->statut)->toBe('PAYEE');
});

test('the factures page offers the payment methods and the remaining balance', function () {
    $client = Client::factory()->create();
    Facture::factory()->create(['client_id' => $client->id, 'montant' => 90000]);

    $this->actingAs($client->user)
        ->get(route('espace-client.factures'))
        ->assertInertia(fn ($page) => $page
            ->where('factures.0.solde', '90000')
            ->where('factures.0.paiementEnAttente', false)
            ->has('moyensPaiement', 3));
});

test('the provider webhook confirms a payment when the signature is valid, once', function () {
    config(['paiements.webhook_secret' => 'secret-de-test']);
    $facture = Facture::factory()->create(['montant' => 60000]);
    $paiement = Paiement::create([
        'facture_id' => $facture->id, 'montant' => 60000, 'moyen' => 'OM',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => 'REF-123',
    ]);

    $corps = json_encode(['reference' => 'REF-123', 'statut' => 'success']);
    $signature = hash_hmac('sha256', $corps, 'secret-de-test');

    foreach ([1, 2] as $_) {
        $this->call('POST', route('paiements.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
        ], $corps)->assertOk();
    }

    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect((float) $facture->fresh()->montant_paye)->toBe(60000.0);
});

test('the provider webhook rejects a bad signature and an unconfigured secret', function () {
    $facture = Facture::factory()->create();
    $paiement = Paiement::create([
        'facture_id' => $facture->id, 'montant' => 1000, 'moyen' => 'OM',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => 'REF-9',
    ]);
    $corps = json_encode(['reference' => 'REF-9', 'statut' => 'success']);
    $entetes = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    config(['paiements.webhook_secret' => null]);
    $this->call('POST', route('paiements.webhook'), [], [], [], $entetes, $corps)->assertForbidden();

    config(['paiements.webhook_secret' => 'secret-de-test']);
    $this->call('POST', route('paiements.webhook'), [], [], [], $entetes + ['HTTP_X_SIGNATURE' => 'mauvaise'], $corps)->assertForbidden();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
});

test('an admin can confirm or fail a payment left pending', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $facture = Facture::factory()->create(['montant' => 40000]);
    $enAttente = Paiement::create(['facture_id' => $facture->id, 'montant' => 40000, 'moyen' => 'OM', 'statut' => 'EN_ATTENTE']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.paiements.update', $enAttente), ['statut' => 'CONFIRME'])
        ->assertRedirect();

    expect($enAttente->fresh()->statut)->toBe('CONFIRME');
    expect($enAttente->fresh()->valide_par_id)->toBe($promotrice->id);
    expect($facture->fresh()->statut)->toBe('PAYEE');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.paiements.update', $enAttente), ['statut' => 'ECHEC'])
        ->assertStatus(409);
});

test('a lecture-only admin role cannot settle a pending payment', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $facture = Facture::factory()->create();
    $enAttente = Paiement::create(['facture_id' => $facture->id, 'montant' => 1000, 'moyen' => 'OM', 'statut' => 'EN_ATTENTE']);

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.paiements.update', $enAttente), ['statut' => 'CONFIRME'])
        ->assertForbidden();
});
