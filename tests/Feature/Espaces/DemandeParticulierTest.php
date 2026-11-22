<?php

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\DevisRecuNotification;
use App\Support\Parcours\ParcoursDemande;
use Illuminate\Support\Facades\Notification;

/**
 * A particulier's demande whose devis DAG sent for the given amount.
 *
 * @return array{0: Client, 1: Demande, 2: Facture}
 */
function devisEnvoyeAuParticulier(float $montant = 80000): array
{
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ETUDE']);

    test()->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => $montant, 'message' => 'Livraison sous deux semaines.'])
        ->assertRedirect();

    return [$client, $demande->fresh(), Facture::where('demande_id', $demande->id)->sole()];
}

test('a particulier receives the devis as a proforma to validate, without any contract', function () {
    Notification::fake();

    [$client, $demande, $facture] = devisEnvoyeAuParticulier();

    expect($demande->statut)->toBe('DEVIS_ENVOYE');
    expect((float) $facture->montant)->toBe(80000.0);
    expect(Contrat::count())->toBe(0);
    Notification::assertSentTo($client->user, DevisRecuNotification::class, fn ($notification) => $notification->libelleAction() === 'Voir et valider le devis');

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.demandes.show', $demande))
        ->assertInertia(fn ($page) => $page
            ->where('demande.estParticulier', true)
            ->where('demande.devis.peutValider', true)
            ->where('demande.facture.attendValidationDevis', true)
            ->where('demande.facture.attendSignatureContrat', false)
            ->where('parcours.prochaine.action', 'valider_devis')
            ->where('suivi.etapes', fn ($etapes) => ! collect($etapes)->contains('CONTRAT_SIGNE')));
});

test('the payment only opens once the particulier validated the devis, and the team is told', function () {
    [$client, $demande, $facture] = devisEnvoyeAuParticulier();
    $controle = MembreEquipe::factory()->promotrice()->create();
    Notification::fake();

    $this->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertSessionHasErrors('moyen');
    expect(Paiement::count())->toBe(0);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
        ->assertRedirect();

    expect($demande->fresh()->devis_valide_le)->not->toBeNull();
    expect(ActivityLog::where('action', 'devis validé par le client')->count())->toBe(1);
    Notification::assertSentTo($controle, AlerteEquipeNotification::class);

    $this->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertSessionHasNoErrors();
    expect(Paiement::count())->toBe(1);
});

test('a devis can be validated once, only by its client, and only once sent', function () {
    [$client, $demande] = devisEnvoyeAuParticulier();

    $this->actingAs(Client::factory()->create()->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
        ->assertForbidden();

    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande))->assertRedirect();
    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande))->assertStatus(409);

    $sansDevis = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ETUDE']);
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $sansDevis))
        ->assertStatus(409);
});

test('a new devis for a particulier must be validated again', function () {
    [$client, $demande] = devisEnvoyeAuParticulier(80000);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande));

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.demandes.devis', $demande), ['montant' => 90000]);
    expect($demande->fresh()->devis_valide_le)->toBeNull();
    expect((float) Facture::where('demande_id', $demande->id)->sole()->montant)->toBe(90000.0);

    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande));
    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 95000])
        ->assertSessionHasNoErrors();
    expect($demande->fresh()->devis_valide_le)->toBeNull();
    expect(Contrat::count())->toBe(0);
});

test('the proforma PDF is available to the client and the team once the devis is sent', function () {
    $client = Client::factory()->create();
    $avant = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ETUDE']);

    $this->actingAs($client->user, 'web')->get(route('espace-client.demandes.proforma', $avant))->assertNotFound();

    [$client, $demande] = devisEnvoyeAuParticulier();

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.demandes.proforma', $demande))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('proforma-'.str_replace('DAG-DEM-', 'DAG-PRO-', $demande->reference).'.pdf');

    $this->actingAs(Client::factory()->create()->user, 'web')
        ->get(route('espace-client.demandes.proforma', $demande))
        ->assertForbidden();

    $this->actingAs(MembreEquipe::factory()->juriste()->create(), 'admin')
        ->get(route('espace-admin.demandes.proforma', $demande))
        ->assertOk();
});

test('no contract and no « Contrat signé » for a particulier; the prestation waits for the validated devis', function () {
    [$client, $demande] = devisEnvoyeAuParticulier();
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.store'), ['demande_id' => $demande->id])
        ->assertStatus(409);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'CONTRAT_SIGNE'])
        ->assertSessionHasErrors('statut');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'EN_COURS'])
        ->assertSessionHasErrors('statut');

    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande));

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'EN_COURS'])
        ->assertSessionHasNoErrors();
    expect($demande->fresh()->statut)->toBe('EN_COURS');
    expect(Contrat::count())->toBe(0);
});

test('the team sees particuliers and entreprises apart, each with its own procedure', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $particulier = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 50000]);
    $entreprise = Demande::factory()->entreprise()->create(['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 50000]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertInertia(function ($page) use ($particulier, $entreprise) {
            $demandes = collect($page->toArray()['props']['demandes'])->keyBy('id');

            expect($demandes[$particulier->id]['typeClient'])->toBe('particulier');
            expect($demandes[$entreprise->id]['typeClient'])->toBe('entreprise');
        });

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.show', $particulier))
        ->assertInertia(fn ($page) => $page
            ->where('demande.estParticulier', true)
            ->where('peutGenererContrat', false)
            ->where('suivi.etapes', fn ($etapes) => ! collect($etapes)->contains('CONTRAT_SIGNE')));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.show', $entreprise))
        ->assertInertia(fn ($page) => $page
            ->where('demande.estParticulier', false)
            ->where('suivi.etapes', fn ($etapes) => collect($etapes)->contains('CONTRAT_SIGNE')));
});

test('each stage of a particulier demande tells who acts next', function (string $statut, bool $devisValide, ?string $facture, string $etape, string $acteur) {
    $demande = Demande::factory()->create([
        'statut' => $statut,
        'montant_negocie' => 50000,
        'devis_valide_le' => $devisValide ? now() : null,
    ]);

    if ($facture !== null) {
        Facture::factory()->create(['demande_id' => $demande->id, 'montant' => 50000, 'statut' => $facture, 'montant_paye' => $facture === 'PAYEE' ? 50000 : 0]);
    }

    $parcours = ParcoursDemande::pour($demande->fresh(), 'client');
    $enCours = collect($parcours['etapes'])->firstWhere('etat', 'en_cours');

    expect(collect($parcours['etapes'])->pluck('cle')->all())->toBe(['demande', 'devis', 'acceptation', 'paiement', 'prestation', 'cloture']);
    expect([$enCours['cle'], $parcours['prochaine']['acteur']])->toBe([$etape, $acteur]);
})->with([
    'just sent' => ['EN_ETUDE', false, null, 'devis', 'dag'],
    'devis to validate' => ['DEVIS_ENVOYE', false, 'EN_ATTENTE', 'acceptation', 'client'],
    'validated, to pay' => ['DEVIS_ENVOYE', true, 'EN_ATTENTE', 'paiement', 'client'],
    'paid' => ['PAYEE', true, 'PAYEE', 'prestation', 'dag'],
]);

test('a particulier can decline the devis with a reason and get a revised proposal', function () {
    [$client, $demande, $facture] = devisEnvoyeAuParticulier(80000);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    Notification::fake();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), [
            'motif' => 'PRIX',
            'commentaire' => 'Mon budget est de 60 000 FCFA.',
            'suite' => 'nouveau_devis',
        ])
        ->assertRedirect();

    $demande->refresh();
    expect($demande->statut)->toBe('EN_ETUDE');
    expect($demande->motif_refus_devis)->toBe('Le prix est trop élevé — Mon budget est de 60 000 FCFA.');
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);

    $this->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertSessionHasErrors('moyen');

    $admin = ParcoursDemande::pour($demande, 'admin');
    expect($admin['prochaine']['action'])->toBe('envoyer_devis');
    expect($admin['prochaine']['texte'])->toContain('Mon budget est de 60 000 FCFA.');

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.demandes.devis', $demande), ['montant' => 65000]);

    $demande->refresh();
    expect($demande->statut)->toBe('DEVIS_ENVOYE');
    expect($demande->devisEstRefuse())->toBeFalse();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
        ->assertRedirect();
    expect($demande->fresh()->devisEstValide())->toBeTrue();
});

test('a particulier who does not wish to go on closes the demande', function () {
    [$client, $demande] = devisEnvoyeAuParticulier();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'BESOIN', 'suite' => 'abandon'])
        ->assertRedirect();

    $demande->refresh();
    expect($demande->statut)->toBe('REFUSEE');
    expect(ParcoursDemande::pour($demande, 'client')['interrompu'])->toContain('Vous avez décliné le devis');
    expect(ParcoursDemande::pour($demande, 'admin')['interrompu'])->toContain('Mon besoin a changé');
});

test('declining a devis needs a known reason, details for « autre », and an open devis', function () {
    [$client, $demande] = devisEnvoyeAuParticulier();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'INCONNU', 'suite' => 'abandon'])
        ->assertSessionHasErrors('motif');

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'AUTRE', 'suite' => 'nouveau_devis'])
        ->assertSessionHasErrors('commentaire');

    $this->actingAs(Client::factory()->create()->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'PRIX', 'suite' => 'abandon'])
        ->assertForbidden();

    expect($demande->fresh()->statut)->toBe('DEVIS_ENVOYE');

    $this->actingAs($client->user, 'web')->post(route('espace-client.demandes.devis.valider', $demande));

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'PRIX', 'suite' => 'abandon'])
        ->assertStatus(409);
    expect($demande->fresh()->devisEstValide())->toBeTrue();
});

test('an entreprise also receives a proforma it can decline, and no contract is sent then', function () {
    $entreprise = Client::factory()->entreprise()->create();
    $demande = Demande::factory()->create(['client_id' => $entreprise->id, 'statut' => 'EN_ETUDE']);
    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 250000]);

    $this->actingAs($entreprise->user, 'web')
        ->get(route('espace-client.demandes.proforma', $demande))
        ->assertOk();

    $this->actingAs($entreprise->user, 'web')
        ->post(route('espace-client.demandes.devis.refuser', $demande), ['motif' => 'DELAI', 'suite' => 'nouveau_devis'])
        ->assertRedirect();

    expect($demande->fresh()->statut)->toBe('EN_ETUDE');
    expect(Contrat::count())->toBe(0);
});

test('a particulier demande started under the contract procedure keeps going', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'CONTRAT_SIGNE', 'montant_negocie' => 60000]);
    Contrat::factory()->valide()->create(['client_id' => $client->id, 'demande_id' => $demande->id]);
    $facture = Facture::factory()->create(['client_id' => $client->id, 'demande_id' => $demande->id, 'montant' => 60000]);

    $this->actingAs($client->user, 'web')
        ->post(route('paiements.initier', $facture), ['moyen' => 'OM', 'telephone' => '690000000'])
        ->assertSessionHasNoErrors();

    expect(Paiement::count())->toBe(1);
});
