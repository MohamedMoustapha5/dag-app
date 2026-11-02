<?php

use App\Enums\MembreEquipeRole;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;

test('a controle de gestion can generate a facture for a demande awaiting payment', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE', 'montant_negocie' => 125000]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.factures.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-factures')
            ->has('demandesAFacturer', 1)
            ->where('demandesAFacturer.0.id', $demande->id));

    $this->actingAs($membre, 'admin')->post(route('espace-admin.factures.store'), [
        'demande_id' => $demande->id,
        'montant' => 125000,
    ])->assertRedirect();

    $facture = Facture::where('demande_id', $demande->id)->firstOrFail();
    expect($facture->statut)->toBe('EN_ATTENTE');
    expect((float) $facture->montant)->toBe(125000.0);
});

test('a demande already billed cannot be billed twice', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    Facture::factory()->create(['demande_id' => $demande->id, 'client_id' => null]);

    $this->actingAs($membre, 'admin')->post(route('espace-admin.factures.store'), [
        'demande_id' => $demande->id,
        'montant' => 50000,
    ])->assertStatus(409);
});

test('recording a full payment marks the facture and the linked demande as paid', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $demande = Demande::factory()->create(['statut' => 'DEVIS_ENVOYE']);
    $facture = Facture::factory()->create([
        'demande_id' => $demande->id,
        'client_id' => null,
        'montant' => 100000,
        'montant_paye' => 0,
    ]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.factures.show', $facture))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-facture-show')
            ->where('facture.reference', $facture->reference));

    $this->actingAs($membre, 'admin')->post(route('espace-admin.factures.paiements.store', $facture), [
        'montant' => 100000,
        'moyen' => 'MTN_MOMO',
    ])->assertRedirect();

    expect($facture->fresh()->statut)->toBe('PAYEE');
    expect($demande->fresh()->statut)->toBe('PAYEE');
});

test('recording a partial payment leaves the facture partially paid', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $facture = Facture::factory()->create(['montant' => 100000, 'montant_paye' => 0]);

    $this->actingAs($membre, 'admin')->post(route('espace-admin.factures.paiements.store', $facture), [
        'montant' => 40000,
        'moyen' => 'ESPECES',
    ])->assertRedirect();

    $facture->refresh();
    expect($facture->statut)->toBe('PARTIELLEMENT_PAYEE');
    expect((float) $facture->montant_paye)->toBe(40000.0);
});

test('a role without access to factures paiements cannot reach the pages', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.factures.index'))
        ->assertForbidden();
});

test('juriste has no access to factures but controle de gestion has full access', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.factures.index'))
        ->assertForbidden();
});
