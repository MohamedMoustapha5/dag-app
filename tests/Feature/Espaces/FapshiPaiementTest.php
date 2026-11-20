<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'paiements.passerelle' => 'fapshi',
        'paiements.fapshi.url' => 'https://sandbox.fapshi.com',
        'paiements.fapshi.api_user' => 'utilisateur-test',
        'paiements.fapshi.api_key' => 'cle-test',
        'paiements.fapshi.webhook_secret' => 'secret-fapshi',
        'paiements.fapshi.paiement_direct' => false,
    ]);
    Http::preventStrayRequests();
});

/**
 * Fakes Fapshi's payment-status endpoint for one transaction.
 *
 * @param  array<string, mixed>  $transaction
 */
function fakeStatutFapshi(string $reference, array $transaction): void
{
    Http::fake([
        "sandbox.fapshi.com/payment-status/{$reference}" => Http::response(['transId' => $reference, 'transType' => 'Collection'] + $transaction),
    ]);
}

function paiementFapshiEnAttente(float $montant, string $reference): Paiement
{
    $facture = Facture::factory()->create(['montant' => $montant]);

    return Paiement::create([
        'facture_id' => $facture->id, 'montant' => $montant, 'moyen' => 'MTN_MOMO',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => $reference,
    ]);
}

/**
 * @param  array<string, mixed>  $donnees
 */
function notifierFapshi(array $donnees, ?string $secret = 'secret-fapshi'): TestResponse
{
    $entetes = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    if ($secret !== null) {
        $entetes['HTTP_X_WH_SECRET'] = $secret;
    }

    return test()->call('POST', route('paiements.webhook'), [], [], [], $entetes, (string) json_encode($donnees));
}

test('starting a payment opens a Fapshi payment page and redirects the payer to it', function () {
    Http::fake([
        'sandbox.fapshi.com/initiate-pay' => Http::response([
            'message' => 'Request successful',
            'link' => 'https://checkout.fapshi.com/payment/abc',
            'transId' => 'fp_123',
            'dateInitiated' => '2026-11-17',
        ]),
    ]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 75000]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertRedirect('https://checkout.fapshi.com/payment/abc');

    $paiement = Paiement::sole();
    expect($paiement->statut)->toBe('EN_ATTENTE');
    expect($paiement->reference_transaction)->toBe('fp_123');

    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://sandbox.fapshi.com/initiate-pay'
        && $requete->hasHeader('apiuser', 'utilisateur-test')
        && $requete->hasHeader('apikey', 'cle-test')
        && $requete['amount'] === 75000
        && $requete['email'] === $client->user->email
        && $requete['externalId'] === (string) $paiement->id
        && $requete['redirectUrl'] === route('paiements.retour', $paiement));
});

test('with direct payment on, the request goes straight to the payer\'s phone', function (string $moyen, string $medium) {
    config(['paiements.fapshi.paiement_direct' => true]);
    Http::fake(['sandbox.fapshi.com/direct-pay' => Http::response(['message' => 'Request successful', 'transId' => 'fp_direct'])]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 5000]);

    $this->actingAs($client->user)
        ->from(route('espace-client.factures'))
        ->post(route('paiements.initier', $facture), ['moyen' => $moyen, 'telephone' => '6 70 00 00 00'])
        ->assertRedirect(route('espace-client.factures'));

    expect(Paiement::sole()->reference_transaction)->toBe('fp_direct');
    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://sandbox.fapshi.com/direct-pay'
        && $requete['phone'] === '670000000'
        && $requete['medium'] === $medium
        && $requete['amount'] === 5000);
})->with([
    'Orange Money' => ['OM', 'orange money'],
    'MTN MoMo' => ['MTN_MOMO', 'mobile money'],
]);

test('Fapshi only takes mobile money: the card is neither offered nor accepted', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->get(route('espace-client.factures'))
        ->assertInertia(fn ($page) => $page->where('moyensPaiement', [
            ['value' => 'OM', 'label' => 'Orange Money', 'mobile' => true],
            ['value' => 'MTN_MOMO', 'label' => 'MTN Mobile Money', 'mobile' => true],
        ]));

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::count())->toBe(0);
    Http::assertNothingSent();
});

test('a payment Fapshi refuses is closed and the payer is told to retry', function () {
    Http::fake(['sandbox.fapshi.com/initiate-pay' => Http::response(['message' => 'Invalid amount'], 400)]);
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'MTN_MOMO', 'telephone' => '670000000'])
        ->assertSessionHasErrors('moyen');

    expect(Paiement::sole()->statut)->toBe('ECHEC');
});

test('a Fapshi webhook with the shared secret confirms the payment from the checked status', function () {
    $paiement = paiementFapshiEnAttente(60000, 'fp_ok');
    fakeStatutFapshi('fp_ok', ['status' => 'SUCCESSFUL', 'amount' => 60000]);

    notifierFapshi(['transId' => 'fp_ok', 'status' => 'SUCCESSFUL', 'transType' => 'Collection'])->assertOk();

    expect($paiement->fresh()->statut)->toBe('CONFIRME');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(60000.0);
});

test('a Fapshi webhook claiming success is not credited when the checked status failed', function () {
    $paiement = paiementFapshiEnAttente(60000, 'fp_ko');
    fakeStatutFapshi('fp_ko', ['status' => 'FAILED', 'amount' => 60000]);

    notifierFapshi(['transId' => 'fp_ko', 'status' => 'SUCCESSFUL'])->assertOk();

    expect($paiement->fresh()->statut)->toBe('ECHEC');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(0.0);
});

test('a Fapshi webhook without the right secret is refused', function (?string $secret) {
    $paiement = paiementFapshiEnAttente(60000, 'fp_faux');

    notifierFapshi(['transId' => 'fp_faux', 'status' => 'SUCCESSFUL'], $secret)->assertForbidden();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
    Http::assertNothingSent();
})->with(['missing' => null, 'wrong' => 'mauvais-secret']);

test('back from the Fapshi page, an expired link closes the payment', function () {
    $paiement = paiementFapshiEnAttente(60000, 'fp_expire');
    $paiement->facture->update(['client_id' => ($client = Client::factory()->create())->id]);
    fakeStatutFapshi('fp_expire', ['status' => 'EXPIRED', 'amount' => 60000]);

    $this->actingAs($client->user)
        ->get(route('paiements.retour', $paiement))
        ->assertInertia(fn ($page) => $page->component('public/paiement-resultat')->where('paiement.statut', 'ECHEC'));

    expect($paiement->fresh()->statut)->toBe('ECHEC');
});
