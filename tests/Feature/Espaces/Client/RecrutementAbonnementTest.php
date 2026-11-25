<?php

use App\Models\AccesRecrutement;
use App\Models\Client;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\ForfaitRecrutement;
use App\Models\Paiement;
use App\Notifications\AccesRecrutementOuvertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function forfait(string $code): ForfaitRecrutement
{
    return ForfaitRecrutement::where('code', $code)->sole();
}

/**
 * The entreprise pays its pending subscription facture through the
 * simulated provider.
 */
function payerAbonnement(Client $client, Facture $facture): void
{
    test()->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000']);

    test()->actingAs($client->user, 'web')
        ->post(route('paiements.simulation.decision', Paiement::where('facture_id', $facture->id)->latest()->firstOrFail()), ['resultat' => 'succes']);
}

test('an entreprise without subscription is sent from its recruitment pages to the subscription page', function (string $page) {
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($client->user, 'web')
        ->get(route($page))
        ->assertRedirect(route('espace-client.recrutement.abonnement'));
})->with(['espace-client.offres.index', 'espace-client.candidatures.index']);

test('an expired subscription closes the recruitment pages again', function () {
    $client = Client::factory()->entreprise()->create();
    EntreprisePartenaire::factory()->create(['client_id' => $client->id, 'statut' => 'ACTIF']);
    AccesRecrutement::factory()->expire()->create(['client_id' => $client->id]);

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.offres.index'))
        ->assertRedirect(route('espace-client.recrutement.abonnement'));
});

test('a partner record suspended by DAG keeps the recruitment pages closed despite the subscription', function () {
    $client = Client::factory()->entreprise()->create();
    EntreprisePartenaire::factory()->create(['client_id' => $client->id, 'statut' => 'INACTIF']);
    AccesRecrutement::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.offres.index'))
        ->assertRedirect(route('espace-client.recrutement.abonnement'));
});

test('the subscription page offers the three forfaits at their default prices', function () {
    $client = Client::factory()->entreprise()->create();

    $response = $this->actingAs($client->user, 'web')->get(route('espace-client.recrutement.abonnement'));

    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/recrutement-abonnement')
        ->where('forfaits.0.dureeMois', 1)
        ->where('forfaits.0.prix', '10000.00')
        ->where('forfaits.1.dureeMois', 6)
        ->where('forfaits.1.prix', '55000.00')
        ->where('forfaits.2.dureeMois', 12)
        ->where('forfaits.2.prix', '80000.00')
        ->where('acces.actif', false)
        ->where('factureEnAttente', null));
});

test('choosing a forfait issues its facture and opens its payment', function () {
    $client = Client::factory()->entreprise()->create();
    $semestriel = forfait('SEMESTRIEL');

    $response = $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => $semestriel->id]);

    $facture = Facture::where('client_id', $client->id)->sole();
    $response->assertRedirect()->assertInertiaFlash('payerFacture', $facture->id);
    expect((float) $facture->montant)->toBe(55000.0)
        ->and($facture->statut)->toBe('EN_ATTENTE')
        ->and($facture->accesRecrutement->statut)->toBe('EN_ATTENTE_PAIEMENT')
        ->and($facture->accesRecrutement->duree_mois)->toBe(6);
    expect($client->fresh()->estPartenaireValide())->toBeFalse();
});

test('choosing another forfait before paying switches the same facture', function () {
    $client = Client::factory()->entreprise()->create();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('MENSUEL')->id]);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('ANNUEL')->id]);

    $facture = Facture::where('client_id', $client->id)->sole();
    expect((float) $facture->montant)->toBe(80000.0);
    expect(AccesRecrutement::where('client_id', $client->id)->sole()->duree_mois)->toBe(12);
});

test('a forfait cannot be switched while its payment is in progress', function () {
    $client = Client::factory()->entreprise()->create();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('MENSUEL')->id]);
    $facture = Facture::where('client_id', $client->id)->sole();
    $this->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000']);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('ANNUEL')->id])
        ->assertSessionHasErrors('forfait_id');

    expect((float) $facture->fresh()->montant)->toBe(10000.0);
});

test('an unknown forfait is refused', function (mixed $forfaitId) {
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => $forfaitId])
        ->assertSessionHasErrors('forfait_id');

    expect(Facture::count())->toBe(0);
})->with([null, 'abc', fn () => (string) Str::uuid7()]);

test('paying the subscription opens the recruitment pages for its duration', function () {
    Notification::fake();
    $client = Client::factory()->entreprise()->create(['nom' => 'Batiplus SARL']);
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('SEMESTRIEL')->id]);
    $facture = Facture::where('client_id', $client->id)->sole();

    payerAbonnement($client, $facture);

    $acces = $facture->accesRecrutement->fresh();
    expect($facture->fresh()->statut)->toBe('PAYEE')
        ->and($acces->statut)->toBe('ACTIF')
        ->and($acces->debut->toDateString())->toBe(now()->toDateString())
        ->and($acces->fin->toDateString())->toBe(now()->addMonthsNoOverflow(6)->subDay()->toDateString());
    $this->assertDatabaseHas('entreprises_partenaires', ['client_id' => $client->id, 'nom' => 'Batiplus SARL', 'statut' => 'ACTIF']);
    Notification::assertSentTo($client->user, AccesRecrutementOuvertNotification::class);
    $this->actingAs($client->user->fresh(), 'web')->get(route('espace-client.offres.index'))->assertOk();
});

test('a renewal paid during a subscription starts the day after it ends', function () {
    $client = clientPartenaireValide();
    $finActuelle = $client->finAccesRecrutement();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('MENSUEL')->id]);
    $facture = Facture::where('client_id', $client->id)->sole();

    payerAbonnement($client, $facture);

    $renouvellement = $facture->accesRecrutement->fresh();
    expect($renouvellement->debut->toDateString())->toBe($finActuelle->copy()->addDay()->toDateString());
    expect($client->fresh()->finAccesRecrutement()->toDateString())->toBe($renouvellement->fin->toDateString());
});

test('the subscription is reserved to entreprises and organisations', function () {
    $client = Client::factory()->create(['type' => 'PARTICULIER']);

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.recrutement.abonnement'))
        ->assertForbidden();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => forfait('MENSUEL')->id])
        ->assertForbidden();

    expect(Facture::count())->toBe(0);
});
