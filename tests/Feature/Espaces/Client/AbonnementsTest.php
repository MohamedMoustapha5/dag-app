<?php

use App\Enums\MembreEquipeRole;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Formule;
use App\Models\MembreEquipe;

function abonnementPour(Client $client, array $attributs = []): Abonnement
{
    return Abonnement::query()->create([
        'client_id' => $client->id,
        'formule_id' => Formule::factory()->create(['nom' => 'Croissance'])->id,
        'montant_negocie' => 180000,
        'date_souscription' => now()->subMonths(2),
        'cycle_debut' => now()->subDays(20),
        'echeance' => now()->addDays(10),
        'statut' => 'ACTIF',
        ...$attributs,
    ]);
}

test('a client sees every subscription with its progression and state', function () {
    $client = Client::factory()->create();
    abonnementPour($client);
    abonnementPour($client, ['echeance' => now()->addDays(90), 'cycle_debut' => now()->subDays(10)]);
    abonnementPour($client, ['statut' => 'TERMINE', 'echeance' => now()->subMonth()]);
    abonnementPour($client, ['statut' => 'BROUILLON']);
    abonnementPour(Client::factory()->create());

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/abonnements-index')
            ->has('abonnements', 3)
            ->where('resume.actifs', 2)
            ->where('resume.aRenouveler', 2)
            ->where('abonnements.0.etat', 'bientot')
            ->where('abonnements.0.peutRenouveler', true)
            ->where('abonnements.1.etat', 'actif')
            ->where('abonnements.1.peutRenouveler', false)
            ->where('abonnements.2.etat', 'termine')
            ->where('abonnements.2.progression', 100));
});

test('an expired subscription that is still marked active is flagged as expired', function () {
    $client = Client::factory()->create();
    abonnementPour($client, ['echeance' => now()->subDays(3)]);

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertInertia(fn ($page) => $page
            ->where('abonnements.0.etat', 'expire')
            ->where('abonnements.0.peutRenouveler', true)
            ->where('resume.actifs', 0));
});

test('renewing creates a formule request for the team and is not duplicated', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client);

    $reponse = $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement));

    $demande = $client->demandes()->firstOrFail();
    $reponse->assertRedirect(route('espace-client.demandes.show', $demande));
    expect($demande->formule_id)->toBe($abonnement->formule_id)
        ->and($demande->statut)->toBe('EN_ETUDE')
        ->and($demande->description)->toStartWith('Renouvellement');

    $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertRedirect(route('espace-client.demandes.show', $demande));

    expect($client->demandes()->count())->toBe(1);
});

test('a subscription far from its end cannot be renewed yet', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client, ['echeance' => now()->addDays(120)]);

    $this->actingAs($client->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertStatus(409);

    expect($client->demandes()->count())->toBe(0);
});

test('a client cannot renew the subscription of another client', function () {
    $abonnement = abonnementPour(Client::factory()->create());
    $autre = Client::factory()->create();

    $this->actingAs($autre->user)
        ->post(route('espace-client.abonnements.renouveler', $abonnement))
        ->assertNotFound();
});

test('the progression is split by month and shows the invoice paid for the current month', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client, [
        'cycle_debut' => now()->subDays(40)->toDateString(),
        'echeance' => now()->addDays(50)->toDateString(),
    ]);
    Facture::factory()->payee()->create([
        'client_id' => $client->id,
        'abonnement_id' => $abonnement->id,
        'type' => 'ABONNEMENT',
        'echeance' => now()->subDays(5)->toDateString(),
    ]);

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertInertia(fn ($page) => $page
            ->has('abonnements.0.mois', 3)
            ->where('abonnements.0.mois.1.courant', true)
            ->where('abonnements.0.mois.1.facture', 'PAYEE')
            ->where('abonnements.0.mois.0.avancement', 100)
            ->where('abonnements.0.paiementDuMois', 'PAYEE'));
});

test('an unpaid invoice of the month is reported as waiting for payment', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client, ['cycle_debut' => now()->subDays(5)->toDateString()]);
    Facture::factory()->create([
        'client_id' => $client->id,
        'abonnement_id' => $abonnement->id,
        'type' => 'ABONNEMENT',
        'echeance' => now()->toDateString(),
    ]);

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertInertia(fn ($page) => $page->where('abonnements.0.paiementDuMois', 'EN_ATTENTE'));
});

test('the client sees the pending renewal request instead of a renew button', function () {
    $client = Client::factory()->create();
    $abonnement = abonnementPour($client);

    $this->actingAs($client->user)->post(route('espace-client.abonnements.renouveler', $abonnement));
    $demande = $client->demandes()->firstOrFail();

    $this->actingAs($client->user)
        ->get(route('espace-client.abonnements'))
        ->assertInertia(fn ($page) => $page->where('abonnements.0.demandeRenouvellement', $demande->id));
});

test('the admin subscriptions page flags renewal requests, expiring and expired subscriptions', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $client = Client::factory()->create();
    $aRenouveler = abonnementPour($client);
    abonnementPour(Client::factory()->create(), ['echeance' => now()->subDays(2)]);
    abonnementPour(Client::factory()->create(), ['echeance' => now()->addDays(120), 'cycle_debut' => now()->subDays(5)]);

    $this->actingAs($client->user)->post(route('espace-client.abonnements.renouveler', $aRenouveler));
    $demande = $client->demandes()->firstOrFail();

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.abonnements.index'))
        ->assertInertia(fn ($page) => $page
            ->where('resume.renouvellementsDemandes', 1)
            ->where('resume.bientot', 1)
            ->where('resume.expires', 1)
            ->has('abonnements', 3)
            ->where('abonnements', fn ($abonnements) => collect($abonnements)
                ->contains(fn ($ligne) => ($ligne['demandeRenouvellement']['id'] ?? null) === $demande->id)));

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('abonnementsASurveiller.renouvellementsDemandes', 1)
            ->where('abonnementsASurveiller.expires', 1));
});
