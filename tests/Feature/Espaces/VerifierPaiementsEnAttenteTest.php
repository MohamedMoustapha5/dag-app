<?php

use App\Models\ActivityLog;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'paiements.passerelle' => 'fapshi',
        'paiements.fapshi.url' => 'https://sandbox.fapshi.com',
        'paiements.fapshi.api_user' => 'utilisateur-test',
        'paiements.fapshi.api_key' => 'cle-test',
    ]);
    Http::preventStrayRequests();
});

function paiementResteEnAttente(string $reference, float $montant = 60000, ?DateTimeInterface $creeLe = null): Paiement
{
    $facture = Facture::factory()->create(['montant' => $montant, 'montant_paye' => 0, 'statut' => 'EN_ATTENTE']);

    $paiement = Paiement::create([
        'facture_id' => $facture->id, 'montant' => $montant, 'moyen' => 'OM',
        'statut' => 'EN_ATTENTE', 'reference_transaction' => $reference,
    ]);
    $paiement->forceFill(['created_at' => $creeLe ?? now()->subMinutes(30)])->save();

    return $paiement;
}

/**
 * @param  array<string, array{status: string, amount: int}>  $statuts
 */
function fakeStatutsFapshi(array $statuts): void
{
    $reponses = [];

    foreach ($statuts as $reference => $statut) {
        $reponses["sandbox.fapshi.com/payment-status/{$reference}"] = Http::response(['transId' => $reference] + $statut);
    }

    Http::fake($reponses);
}

test('a pending payment the payer completed without coming back is credited, failed ones are closed and others wait', function () {
    $paye = paiementResteEnAttente('fp_paye');
    $expire = paiementResteEnAttente('fp_expire');
    $enCours = paiementResteEnAttente('fp_en_cours');
    fakeStatutsFapshi([
        'fp_paye' => ['status' => 'SUCCESSFUL', 'amount' => 60000],
        'fp_expire' => ['status' => 'EXPIRED', 'amount' => 60000],
        'fp_en_cours' => ['status' => 'PENDING', 'amount' => 60000],
    ]);

    $this->artisan('paiements:verifier-en-attente')
        ->expectsOutput('1 confirmés, 1 échoués, 1 toujours en attente, 0 non conformes, 0 injoignables.')
        ->assertSuccessful();

    expect($paye->fresh()->statut)->toBe('CONFIRME');
    expect($paye->facture->fresh()->statut)->toBe('PAYEE');
    expect($expire->fresh()->statut)->toBe('ECHEC');
    expect($enCours->fresh()->statut)->toBe('EN_ATTENTE');
});

test('payments still on the provider page, too old, without reference or already settled are not asked about', function () {
    paiementResteEnAttente('fp_trop_recent', creeLe: now()->subMinute());
    paiementResteEnAttente('fp_trop_ancien', creeLe: now()->subDays(8));
    paiementResteEnAttente('fp_confirme')->update(['statut' => 'CONFIRME']);
    paiementResteEnAttente('fp_sans_ref')->update(['reference_transaction' => null]);

    $this->artisan('paiements:verifier-en-attente')->assertSuccessful();

    Http::assertNothingSent();
});

test('a success for another amount is not credited and is reported to the team only once', function () {
    $paiement = paiementResteEnAttente('fp_ecart');
    fakeStatutsFapshi(['fp_ecart' => ['status' => 'SUCCESSFUL', 'amount' => 100]]);

    $this->artisan('paiements:verifier-en-attente')->assertSuccessful();
    $this->artisan('paiements:verifier-en-attente')->assertSuccessful();

    expect($paiement->fresh()->statut)->toBe('EN_ATTENTE');
    expect((float) $paiement->facture->fresh()->montant_paye)->toBe(0.0);
    expect(ActivityLog::where('action', 'paiement en ligne non conforme')->count())->toBe(1);
    Http::assertSentCount(1);
});

test('when the provider cannot be reached for one payment, the others are still checked', function () {
    $injoignable = paiementResteEnAttente('fp_panne');
    $paye = paiementResteEnAttente('fp_ok');
    Http::fake([
        'sandbox.fapshi.com/payment-status/fp_panne' => Http::response(['message' => 'Erreur interne'], 500),
        'sandbox.fapshi.com/payment-status/fp_ok' => Http::response(['transId' => 'fp_ok', 'status' => 'SUCCESSFUL', 'amount' => 60000]),
    ]);
    Log::spy();

    $this->artisan('paiements:verifier-en-attente')
        ->expectsOutput('1 confirmés, 0 échoués, 0 toujours en attente, 0 non conformes, 1 injoignables.')
        ->assertSuccessful();

    expect($injoignable->fresh()->statut)->toBe('EN_ATTENTE');
    expect($paye->fresh()->statut)->toBe('CONFIRME');
    Http::assertSent(fn (Request $requete) => str_ends_with($requete->url(), 'fp_ok'));
});
