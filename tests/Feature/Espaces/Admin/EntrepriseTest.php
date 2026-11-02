<?php

use App\Enums\MembreEquipeRole;
use App\Models\EntreprisePartenaire;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;

test('a promotrice can validate a pending entreprise partner', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $entreprise = EntreprisePartenaire::factory()->create(['statut' => 'EN_ATTENTE']);

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.entreprises.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-entreprises')
            ->has('entreprises', 1)
            ->where('entreprises.0.statut', 'EN_ATTENTE')
            ->where('peutValider', true));

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.entreprises.update', $entreprise), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($entreprise->fresh()->statut)->toBe('ACTIF');
});

test('a promotrice can publish a draft offre and the entreprise client can then see it live', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'BROUILLON']);

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'PUBLIEE'])
        ->assertRedirect();

    expect($offre->fresh()->statut)->toBe('PUBLIEE');
});

test('a promotrice can close a published offre', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'CLOTUREE'])
        ->assertRedirect();

    expect($offre->fresh()->statut)->toBe('CLOTUREE');
});

test('a role with gestion but not validation can see but not publish or validate', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $entreprise = EntreprisePartenaire::factory()->create(['statut' => 'EN_ATTENTE']);
    $offre = OffreEmploi::factory()->create(['statut' => 'BROUILLON']);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.entreprises.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('peutValider', false)
            ->where('peutModifier', true));

    $this->actingAs($membre->user)
        ->patch(route('espace-admin.entreprises.update', $entreprise), ['statut' => 'ACTIF'])
        ->assertForbidden();

    $this->actingAs($membre->user)
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'PUBLIEE'])
        ->assertForbidden();

    expect($entreprise->fresh()->statut)->toBe('EN_ATTENTE');
    expect($offre->fresh()->statut)->toBe('BROUILLON');
});

test('a role without any access to entreprises offres cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.entreprises.index'))
        ->assertForbidden();
});
