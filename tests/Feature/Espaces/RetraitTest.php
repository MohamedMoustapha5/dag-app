<?php

use App\Enums\MembreEquipeRole;
use App\Models\ActivityLog;
use App\Models\Decaissement;
use App\Models\MembreEquipe;
use App\Notifications\AlerteEquipeNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * @return array<string, string>
 */
function retraitValide(): array
{
    return [
        'montant' => '150000',
        'moyen' => 'OM',
        'telephone' => '+237 690 11 22 33',
        'beneficiaire' => 'Dorine Ambassa',
        'motif' => 'Reversement des encaissements d\'octobre',
    ];
}

function activerMoneroo(): void
{
    config([
        'paiements.passerelle' => 'moneroo',
        'paiements.moneroo.cle_secrete' => 'test_cle',
        'paiements.webhook_secret' => 'secret-moneroo',
    ]);
    Http::preventStrayRequests();
}

/**
 * @param  array<string, mixed>  $payout
 */
function fakeVerificationPayout(string $reference, array $payout): void
{
    Http::fake([
        "api.moneroo.io/v1/payouts/{$reference}/verify" => Http::response([
            'message' => 'Payout transaction fetched successfully!',
            'data' => ['id' => $reference] + $payout,
            'errors' => null,
        ]),
    ]);
}

/**
 * @param  array<string, mixed>  $donnees
 */
function notifierPayoutMoneroo(array $donnees): TestResponse
{
    $corps = (string) json_encode($donnees);

    return test()->call('POST', route('paiements.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_MONEROO_SIGNATURE' => hash_hmac('sha256', $corps, 'secret-moneroo'),
    ], $corps);
}

test('a retrait through Moneroo sends the payout to the mobile number and waits for its outcome', function () {
    activerMoneroo();
    Http::fake([
        'api.moneroo.io/v1/payouts/initialize' => Http::response([
            'success' => true,
            'message' => 'Payout transaction initialized successfully',
            'data' => ['id' => 'po_123'],
        ], 201),
    ]);
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.retraits.store'), retraitValide())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $retrait = Decaissement::sole();
    expect($retrait->statut)->toBe('EN_ATTENTE');
    expect($retrait->reference_transaction)->toBe('po_123');
    expect($retrait->telephone)->toBe('690112233');
    expect($retrait->demande_par_id)->toBe($promotrice->id);

    Http::assertSent(fn (Request $requete) => $requete->hasHeader('Authorization', 'Bearer test_cle')
        && $requete['amount'] === 150000
        && $requete['currency'] === 'XAF'
        && $requete['method'] === 'orange_cm'
        && $requete['recipient'] === ['msisdn' => '237690112233']
        && $requete['customer']['first_name'] === 'Dorine'
        && $requete['customer']['last_name'] === 'Ambassa');
});

test('a retrait Moneroo refuses is recorded as failed and the error is shown', function () {
    activerMoneroo();
    Http::fake(['api.moneroo.io/v1/payouts/initialize' => Http::response(['message' => 'Insufficient balance'], 422)]);

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.retraits.store'), retraitValide())
        ->assertSessionHasErrors('montant');

    expect(Decaissement::sole()->statut)->toBe('ECHEC');
});

test('the signed payout webhook settles the retrait from the verified payout, once', function () {
    activerMoneroo();
    $retrait = Decaissement::factory()->create(['reference_transaction' => 'po_ok']);
    fakeVerificationPayout('po_ok', ['status' => 'success', 'amount' => 150000, 'currency' => ['code' => 'XAF']]);

    foreach ([1, 2] as $_) {
        notifierPayoutMoneroo(['event' => 'payout.success', 'data' => ['id' => 'po_ok']])->assertOk();
    }

    expect($retrait->fresh()->statut)->toBe('CONFIRME');
    expect($retrait->fresh()->date_execution)->not->toBeNull();
    expect(ActivityLog::where('action', 'retrait effectué')->count())->toBe(1);
});

test('a failed payout marks the retrait failed with the reason and alerts the team', function () {
    activerMoneroo();
    Notification::fake();
    $controle = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $retrait = Decaissement::factory()->create(['reference_transaction' => 'po_ko']);
    fakeVerificationPayout('po_ko', ['status' => 'failed', 'disburse' => ['failure_message' => 'Numéro invalide']]);

    notifierPayoutMoneroo(['event' => 'payout.success', 'data' => ['id' => 'po_ko']])->assertOk();

    expect($retrait->fresh()->statut)->toBe('ECHEC');
    expect($retrait->fresh()->motif_echec)->toBe('Numéro invalide');
    Notification::assertSentTo($controle, AlerteEquipeNotification::class);
});

test('staff can ask Moneroo for a retrait still pending', function () {
    activerMoneroo();
    $retrait = Decaissement::factory()->create(['reference_transaction' => 'po_attente']);
    fakeVerificationPayout('po_attente', ['status' => 'success', 'amount' => 150000]);

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.retraits.actualiser', $retrait))
        ->assertRedirect();

    expect($retrait->fresh()->statut)->toBe('CONFIRME');
});

test('with the simulation provider a retrait is carried out at once', function () {
    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.retraits.store'), retraitValide())
        ->assertRedirect();

    expect(Decaissement::sole()->statut)->toBe('CONFIRME');
});

test('a retrait needs a valid Cameroonian mobile number', function () {
    activerMoneroo();

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.retraits.store'), ['telephone' => '12345'] + retraitValide())
        ->assertSessionHasErrors('telephone');

    expect(Decaissement::count())->toBe(0);
    Http::assertNothingSent();
});

test('a read-only role sees the retraits but cannot withdraw', function () {
    $developpeur = MembreEquipe::factory()->developpeur()->create();
    $retrait = Decaissement::factory()->create();

    $this->actingAs($developpeur, 'admin')
        ->get(route('espace-admin.retraits.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('espaces/admin-retraits')
            ->where('peutRetirer', false)
            ->has('retraits', 1));

    $this->actingAs($developpeur, 'admin')
        ->post(route('espace-admin.retraits.store'), retraitValide())
        ->assertForbidden();

    $this->actingAs($developpeur, 'admin')
        ->post(route('espace-admin.retraits.actualiser', $retrait))
        ->assertForbidden();

    expect(Decaissement::count())->toBe(1);
});

test('a role without access to payments cannot open the retraits', function () {
    $this->actingAs(MembreEquipe::factory()->juriste()->create(), 'admin')
        ->get(route('espace-admin.retraits.index'))
        ->assertForbidden();
});
