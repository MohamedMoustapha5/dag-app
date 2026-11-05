<?php

use App\Enums\MembreEquipeRole;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;

test('a promotrice can list and view demandes from clients and entreprises', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ETUDE']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-demandes')
            ->has('demandes', 1)
            ->where('demandes.0.client', 'Aïcha N.'));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.show', $demande))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-demande-show')
            ->where('demande.client', 'Aïcha N.')
            ->where('suivi.etapeActuelle', 1));
});

test('a promotrice can send a devis and move the demande forward', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE', 'montant_negocie' => null]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), [
            'statut' => 'DEVIS_ENVOYE',
            'montant_negocie' => 85000,
        ])
        ->assertRedirect();

    $demande->refresh();
    expect($demande->statut)->toBe('DEVIS_ENVOYE');
    expect((float) $demande->montant_negocie)->toBe(85000.0);
});

test('a promotrice can refuse a demande', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'REFUSEE'])
        ->assertRedirect();

    expect($demande->fresh()->statut)->toBe('REFUSEE');
});

test('a role with only lecture cannot update a demande', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('peutModifier', false));

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'DEVIS_ENVOYE'])
        ->assertForbidden();

    expect($demande->fresh()->statut)->toBe('EN_ETUDE');
});

test('a role without any access to demandes clients cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertForbidden();
});

test('envoyer un devis records the amount and message and moves the demande to DEVIS_ENVOYE', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE', 'montant_negocie' => null]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), [
            'montant' => 120000,
            'message' => 'Livraison sous deux semaines.',
        ])
        ->assertRedirect();

    $demande->refresh();
    expect($demande->statut)->toBe('DEVIS_ENVOYE');
    expect((float) $demande->montant_negocie)->toBe(120000.0);
    expect($demande->message_devis)->toBe('Livraison sous deux semaines.');
});

test('envoyer un devis requires a positive amount', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 0])
        ->assertSessionHasErrors('montant');

    expect($demande->fresh()->statut)->toBe('EN_ETUDE');
});

test('envoyer un devis is refused once the demande is paid', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'PAYEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 5000])
        ->assertStatus(409);
});

test('a role with only lecture cannot send a devis', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($juriste, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 5000])
        ->assertForbidden();

    expect($demande->fresh()->statut)->toBe('EN_ETUDE');
});

test('a demande is flagged as paid from its facture, whatever its progress status', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $payeeEnCours = Demande::factory()->create(['statut' => 'EN_COURS']);
    Facture::factory()->payee()->create(['demande_id' => $payeeEnCours->id]);
    $terminee = Demande::factory()->create(['statut' => 'TERMINEE']);
    $impayee = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    Facture::factory()->create(['demande_id' => $impayee->id]);

    $reponse = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.demandes.index'));

    $reponse->assertInertia(function ($page) use ($payeeEnCours, $terminee, $impayee) {
        $demandes = collect($page->toArray()['props']['demandes'])->keyBy('id');

        expect($demandes[$payeeEnCours->id]['payee'])->toBeTrue();
        expect($demandes[$terminee->id]['payee'])->toBeFalse();
        expect($demandes[$impayee->id]['payee'])->toBeFalse();
    });

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.show', $payeeEnCours))
        ->assertInertia(fn ($page) => $page->where('demande.payee', true)->where('peutGenererContrat', true));
});

test('the demandes list exposes the contract to open or send, according to the role rights', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();
    $demande = Demande::factory()->create(['statut' => 'PAYEE']);
    $contrat = Contrat::factory()->create(['demande_id' => $demande->id, 'statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertInertia(fn ($page) => $page
            ->where('demandes.0.contratId', $contrat->id)
            ->where('demandes.0.contratStatut', 'BROUILLON')
            ->where('peutEnvoyerContrat', true));

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ENVOYE'])
        ->assertRedirect();
    expect($contrat->fresh()->statut)->toBe('ENVOYE');

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertInertia(fn ($page) => $page->where('peutEnvoyerContrat', true));
});

test('a demande cannot be marked as paid by hand while its facture is unpaid', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    Facture::factory()->create(['demande_id' => $demande->id, 'client_id' => null]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'PAYEE'])
        ->assertSessionHasErrors(['statut' => 'Une demande passe à « Payée » quand sa facture est réglée, pas à la main.']);

    expect($demande->fresh()->statut)->toBe('DEVIS_ENVOYE');
});

test('a role without the factures right cannot set the amount billed to the client', function () {
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($marketing, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 150000])
        ->assertForbidden();

    $this->actingAs($marketing, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 150000])
        ->assertSessionHasErrors(['statut' => 'Fixer le montant facturé demande le droit de créer des factures.']);

    expect($demande->fresh()->statut)->toBe('EN_ETUDE');
    expect(Facture::count())->toBe(0);
});

test('a role without the factures right can still move a demande through the other steps', function () {
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $this->actingAs($marketing, 'admin')
        ->patch(route('espace-admin.demandes.update', $demande), ['statut' => 'REFUSEE'])
        ->assertSessionHasNoErrors();

    expect($demande->fresh()->statut)->toBe('REFUSEE');
});
