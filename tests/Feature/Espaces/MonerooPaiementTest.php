<?php

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'paiements.passerelle' => 'moneroo',
        'paiements.moneroo.cle_secrete' => 'test_cle',
        'paiements.webhook_secret' => 'secret-moneroo',
    ]);
    Http::preventStrayRequests();
});

/**
 * Fakes Moneroo's verify endpoint for one transaction.
 *
 * @param  array<string, mixed>  $transaction
 */
function fakeVerificationMoneroo(string $reference, array $transaction): void
{
    Http::fake([
        "api.moneroo.io/v1/payments/{$reference}/verify" => Http::response([
            'message' => 'Payment transaction fetched successfully!',
            'data' => ['id' => $reference] + $transaction,
            'errors' => null,
        ]),
    ]);
}

function paiementMonerooEnAttente(float $montant, string $reference): Paiement
{
    $facture = Facture::factory()->create(['montant' => $montant]);

    return Paiement::create([
        'facture_id' => $facture->id, 'montant' => $montant, 'moyen' => 'OM',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => $reference,
    ]);
}

/**
 * @param  array<string, mixed>  $donnees
 */
function notifierMoneroo(array $donnees, ?string $signature = null): TestResponse
{
    $corps = (string) json_encode($donnees);

    return test()->call('POST', route('paiements.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_MONEROO_SIGNATURE' => $signature ?? hash_hmac('sha256', $corps, 'secret-moneroo'),
    ], $corps);
}

test('starting a payment opens a Moneroo checkout and redirects the payer to it', function () {
    Http::fake([
        'api.moneroo.io/v1/payments/initialize' => Http::response([
            'message' => 'Transaction initialized successfully',
            'data' => ['id' => 'mnr_123', 'checkout_url' => 'https://checkout.moneroo.io/mnr_123'],
        ], 201),
    ]);
    $client = Client::factory()->create();
    $client->user->update(['name' => 'Awa Ngono Mballa']);
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 75000]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertRedirect('https://checkout.moneroo.io/mnr_123');

    $paiement = Paiement::sole();
    expect($paiement->statut)->toBe('EN_ATTENTE');
    expect($paiement->reference_transaction)->toBe('mnr_123');

    Http::assertSent(fn (Request $requete) => $requete->hasHeader('Authorization', 'Bearer test_cle')
        && $requete['amount'] === 75000
        && $requete['currency'] === 'XAF'
        && $requete['methods'] === ['orange_cm']
        && $requete['customer']['first_name'] === 'Awa'
        && $requete['customer']['last_name'] === 'Ngono Mballa'
        && $requete['customer']['phone'] === '+237690000000'
        && $requete['return_url'] === route('paiements.retour', $paiement));
});

test('a payment Moneroo refuses is closed and the payer is told to retry', function () {
    Http::fake(['api.moneroo.io/v1/payments/initialize' => Http::response(['message' => 'Invalid'], 422)]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::sole()->statut)->toBe('ECHEC');
});

test('a signed Moneroo webhook confirms the payment from the verified transaction', function () {
    $paiement = paiementMonerooEnAttente(60000, 'mnr_ok');
    fakeVerificationMoneroo('mnr_ok', ['status' => 'success', 'amount' => 60000, 'currency' => ['code' => 'XAF']]);

    notifierMoneroo(['event' => 'payment.success', 'data' => ['id' => 'mnr_ok']])->assertOk();

    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(60000.0);
});

test('a Moneroo webhook claiming success is not credited when the verified transaction failed', function () {
    $paiement = paiementMonerooEnAttente(60000, 'mnr_ko');
    fakeVerificationMoneroo('mnr_ko', ['status' => 'failed', 'amount' => 60000, 'currency' => ['code' => 'XAF']]);

    notifierMoneroo(['event' => 'payment.success', 'data' => ['id' => 'mnr_ko', 'status' => 'success']])->assertOk();

    expect($paiement->fresh()->statut)->toBe('ECHEC');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(0.0);
});

test('a verified Moneroo success for another amount or currency is not credited', function (int $montant, string $devise) {
    $paiement = paiementMonerooEnAttente(60000, 'mnr_ecart');
    fakeVerificationMoneroo('mnr_ecart', ['status' => 'success', 'amount' => $montant, 'currency' => ['code' => $devise]]);

    notifierMoneroo(['event' => 'payment.success', 'data' => ['id' => 'mnr_ecart']])->assertStatus(422);

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
    expect(ActivityLog::where('action', 'paiement en ligne non conforme')->exists())->toBeTrue();
})->with([
    'montant inférieur' => [100, 'XAF'],
    'autre devise' => [60000, 'XOF'],
]);

test('a Moneroo webhook with a bad signature is refused without calling the API', function () {
    $paiement = paiementMonerooEnAttente(60000, 'mnr_sig');

    notifierMoneroo(['event' => 'payment.success', 'data' => ['id' => 'mnr_sig']], 'mauvaise')->assertForbidden();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
    Http::assertNothingSent();
});

test('the payer back from Moneroo sees the payment confirmed once Moneroo verifies it', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 40000]);
    $paiement = Paiement::create([
        'facture_id' => $facture->id, 'montant' => 40000, 'moyen' => 'MTN_MOMO',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => 'mnr_retour',
    ]);
    fakeVerificationMoneroo('mnr_retour', ['status' => 'success', 'amount' => 40000, 'currency' => ['code' => 'XAF']]);

    $this->actingAs($client->user)
        ->get(route('paiements.retour', $paiement).'?paymentId=mnr_retour&paymentStatus=success')
        ->assertRedirect(route('espace-client.factures'));

    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect($facture->fresh()->statut)->toBe('PAYEE');
});

test('the payer back from Moneroo cannot settle the payment through the query string', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 40000]);
    $paiement = Paiement::create([
        'facture_id' => $facture->id, 'montant' => 40000, 'moyen' => 'OM',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => 'mnr_attente',
    ]);
    fakeVerificationMoneroo('mnr_attente', ['status' => 'pending', 'amount' => 40000, 'currency' => ['code' => 'XAF']]);

    $this->actingAs($client->user)
        ->get(route('paiements.retour', $paiement).'?paymentId=mnr_attente&paymentStatus=success')
        ->assertRedirect(route('espace-client.factures'));

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
});

test('nobody else can use the Moneroo return page of a payment', function () {
    $paiement = paiementMonerooEnAttente(40000, 'mnr_autrui');

    $this->actingAs(Client::factory()->create()->user)
        ->get(route('paiements.retour', $paiement))
        ->assertNotFound();

    Http::assertNothingSent();
});
