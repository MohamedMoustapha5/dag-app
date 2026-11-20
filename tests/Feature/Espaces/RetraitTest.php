<?php

use App\Enums\MembreEquipeRole;
use App\Models\ActivityLog;
use App\Models\Decaissement;
use App\Models\MembreEquipe;
use App\Notifications\AlerteEquipeNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

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

test('the retraits page was removed from the administration', function () {
    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->get('/espace-admin/retraits')
        ->assertNotFound();
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
