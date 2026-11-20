<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use App\Notifications\PaiementConfirmeNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'paiements.passerelle' => 'notchpay',
        'paiements.notchpay.url' => 'https://api.notchpay.co',
        'paiements.notchpay.environnement' => 'sandbox',
        'paiements.notchpay.cle_publique' => 'pk_test_cle',
        'paiements.notchpay.cle_hachage' => 'hsk_test_hachage',
        'paiements.notchpay.paiement_direct' => true,
    ]);
    Http::preventStrayRequests();
});

/**
 * Fakes Notch Pay's retrieve-a-payment endpoint for one transaction.
 *
 * @param  array<string, mixed>  $transaction
 */
function fakeStatutNotchPay(string $reference, array $transaction): void
{
    Http::fake([
        "api.notchpay.co/payments/{$reference}" => Http::response([
            'code' => 202,
            'message' => 'Transaction retrieved',
            'transaction' => ['reference' => $reference, 'currency' => 'XAF'] + $transaction,
        ], 202),
    ]);
}

function paiementNotchPayEnAttente(float $montant, string $reference, ?Client $client = null): Paiement
{
    $facture = Facture::factory()->create(['montant' => $montant] + ($client !== null ? ['client_id' => $client->id] : []));

    return Paiement::create([
        'facture_id' => $facture->id, 'montant' => $montant, 'moyen' => 'MTN_MOMO',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => $reference,
    ]);
}

/**
 * @param  array<string, mixed>  $donnees
 */
function notifierNotchPay(array $donnees, ?string $cle = 'hsk_test_hachage'): TestResponse
{
    $corps = (string) json_encode($donnees);
    $entetes = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    if ($cle !== null) {
        $entetes['HTTP_X_NOTCH_SIGNATURE'] = hash_hmac('sha256', $corps, $cle);
    }

    return test()->call('POST', route('paiements.webhook'), [], [], [], $entetes, $corps);
}

test('starting a payment sends the request to the payer\'s phone and opens DAG\'s result page', function (string $moyen, string $canal) {
    Http::fake([
        'api.notchpay.co/payments' => Http::response([
            'code' => 201,
            'transaction' => ['reference' => 'trx.test_direct', 'status' => 'pending'],
            'authorization_url' => 'https://pay.notchpay.co/pay_direct',
        ], 201),
        'api.notchpay.co/payments/trx.test_direct' => Http::response([
            'code' => 202,
            'action' => 'confirm',
            'transaction' => ['reference' => 'trx.test_direct', 'status' => 'pending'],
        ], 202),
    ]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 450000]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => $moyen, 'telephone' => '670000000'])
        ->assertRedirect(route('paiements.retour', $paiement = Paiement::sole()));

    expect($paiement->reference_transaction)->toBe('trx.test_direct');
    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://api.notchpay.co/payments'
        && $requete['amount'] === 450000
        && $requete['locked_channel'] === $canal);
    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://api.notchpay.co/payments/trx.test_direct'
        && $requete->method() === 'POST'
        && $requete['channel'] === $canal
        && $requete['data'] === ['phone' => '+237670000000']);
})->with([
    'Orange Money' => ['OM', 'cm.orange'],
    'MTN MoMo' => ['MTN_MOMO', 'cm.mtn'],
]);

test('a direct charge Notch Pay refuses closes the payment and the payer is told to retry', function () {
    Http::fake([
        'api.notchpay.co/payments' => Http::response(['transaction' => ['reference' => 'trx.test_refus']], 201),
        'api.notchpay.co/payments/trx.test_refus' => Http::response(['message' => 'Invalid phone'], 422),
    ]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'MTN_MOMO', 'telephone' => '670000000'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::sole()->statut)->toBe('ECHEC');
});

test('with direct payment off, the payer is redirected to the Notch Pay checkout page', function (string $moyen, string $canal) {
    config(['paiements.notchpay.paiement_direct' => false]);
    Http::fake([
        'api.notchpay.co/payments' => Http::response([
            'status' => 'Accepted',
            'code' => 201,
            'transaction' => ['reference' => 'trx.test_123', 'status' => 'pending'],
            'authorization_url' => 'https://pay.notchpay.co/pay_123',
        ], 201),
    ]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 75000]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => $moyen, 'telephone' => '6 70 00 00 00'])
        ->assertRedirect('https://pay.notchpay.co/pay_123');

    $paiement = Paiement::sole();
    expect($paiement->statut)->toBe('EN_ATTENTE');
    expect($paiement->reference_transaction)->toBe('trx.test_123');

    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://api.notchpay.co/payments'
        && $requete->hasHeader('Authorization', 'pk_test_cle')
        && $requete['amount'] === 75000
        && $requete['currency'] === 'XAF'
        && $requete['email'] === $client->user->email
        && $requete['phone'] === '+237670000000'
        && $requete['reference'] === (string) $paiement->id
        && $requete['callback'] === route('paiements.retour', $paiement)
        && $requete['locked_channel'] === $canal);
})->with([
    'Orange Money' => ['OM', 'cm.orange'],
    'MTN MoMo' => ['MTN_MOMO', 'cm.mtn'],
]);

test('in sandbox, a live Notch Pay key is refused before anything is sent', function () {
    config(['paiements.notchpay.cle_publique' => 'pk_live_cle']);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::sole()->statut)->toBe('ECHEC');
    Http::assertNothingSent();
});

test('a facture above the Notch Pay limit is refused with the reason before anything is sent', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 870147]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'MTN_MOMO', 'telephone' => '670000000'])
        ->assertSessionHasErrors(['moyen' => 'Le paiement mobile est limité à 500 000 FCFA par transaction : contactez DAG pour régler cette facture.']);

    expect(Paiement::sole()->statut)->toBe('ECHEC');
    Http::assertNothingSent();
});

test('Notch Pay only takes mobile money here: the card is not accepted', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::count())->toBe(0);
});

test('a signed Notch Pay webhook confirms the payment from the checked status and e-mails the payer', function () {
    Notification::fake();
    $client = Client::factory()->create();
    $paiement = paiementNotchPayEnAttente(60000, 'trx.ok', $client);
    fakeStatutNotchPay('trx.ok', ['status' => 'complete', 'amount' => 60000]);

    notifierNotchPay(['type' => 'payment.complete', 'data' => ['reference' => 'trx.ok']])->assertOk();

    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect($paiement->facture->fresh()->statut)->toBe('PAYEE');
    Notification::assertSentTo($client->user, PaiementConfirmeNotification::class);
});

test('a Notch Pay webhook claiming success is not credited when the checked status failed', function () {
    $paiement = paiementNotchPayEnAttente(60000, 'trx.ko');
    fakeStatutNotchPay('trx.ko', ['status' => 'canceled', 'amount' => 60000]);

    notifierNotchPay(['type' => 'payment.complete', 'data' => ['reference' => 'trx.ko']])->assertOk();

    expect($paiement->fresh()->statut)->toBe('ECHEC');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(0.0);
});

test('a Notch Pay webhook without a valid signature is refused', function (?string $cle) {
    $paiement = paiementNotchPayEnAttente(60000, 'trx.faux');

    notifierNotchPay(['type' => 'payment.complete', 'data' => ['reference' => 'trx.faux']], $cle)->assertForbidden();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
    Http::assertNothingSent();
})->with(['missing' => null, 'wrong' => 'mauvaise-cle']);

test('a Notch Pay event that is not about a payment is ignored', function () {
    notifierNotchPay(['type' => 'transfer.complete', 'data' => ['reference' => 'trf.1']])
        ->assertOk()
        ->assertJson(['statut' => 'ignore']);

    Http::assertNothingSent();
});

test('back from Notch Pay, the payer sees the outcome checked with Notch Pay', function (string $statutNotchPay, string $statutAffiche) {
    $client = Client::factory()->create();
    $paiement = paiementNotchPayEnAttente(60000, 'trx.retour', $client);
    fakeStatutNotchPay('trx.retour', ['status' => $statutNotchPay, 'amount' => 60000]);

    $this->actingAs($client->user)
        ->get(route('paiements.retour', $paiement).'?reference=trx.retour&status=complete')
        ->assertInertia(fn ($page) => $page
            ->component('public/paiement-resultat')
            ->where('paiement.statut', $statutAffiche)
            ->where('retour', route('espace-client.factures')));

    expect($paiement->fresh()->statut)->toBe($statutAffiche);
})->with([
    'complete' => ['complete', 'CONFIRME'],
    'expired' => ['expired', 'ECHEC'],
    'still pending' => ['pending', 'EN_ATTENTE'],
]);

test('back from Notch Pay with another amount than the facture, the payment is flagged for review', function () {
    $client = Client::factory()->create();
    $paiement = paiementNotchPayEnAttente(60000, 'trx.ecart', $client);
    fakeStatutNotchPay('trx.ecart', ['status' => 'complete', 'amount' => 100]);

    $this->actingAs($client->user)
        ->get(route('paiements.retour', $paiement))
        ->assertInertia(fn ($page) => $page->where('paiement.statut', 'A_VERIFIER'));

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
});
