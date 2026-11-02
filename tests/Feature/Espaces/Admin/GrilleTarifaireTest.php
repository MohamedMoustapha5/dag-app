<?php

use App\Enums\MembreEquipeRole;
use App\Models\LigneTarifaire;
use App\Models\MembreEquipe;

test('the index page exposes the actions allowed for the current role', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();

    $response = $this->actingAs($juriste, 'admin')->get(route('espace-admin.grille-tarifaire.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-grille-tarifaire')
        ->where('actions.creer', false)
        ->where('actions.modifier', false)
        ->where('actions.supprimer', false)
        ->where('droits.CATALOGUE', 'LECTURE'));
});

test('a promotrice can create, modify and delete lignes tarifaires', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $ligne = LigneTarifaire::factory()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.grille-tarifaire.store'), [
        'categorie' => 'Graphisme & identité visuelle',
        'categorie_ordre' => 1,
        'prestation' => 'Logo simple',
        'tarif' => '25 000–60 000',
    ])->assertRedirect();
    $this->assertDatabaseHas('lignes_tarifaires', ['prestation' => 'Logo simple']);

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.grille-tarifaire.update', $ligne), [
        'prestation' => 'Nouveau nom',
        'tarif' => 'Sur devis',
    ])->assertRedirect();
    expect($ligne->fresh()->prestation)->toBe('Nouveau nom');

    $this->actingAs($promotrice, 'admin')->delete(route('espace-admin.grille-tarifaire.destroy', $ligne))->assertRedirect();
    $this->assertDatabaseMissing('lignes_tarifaires', ['id' => $ligne->id]);
});

test('a role with gestion can create and modify but not delete', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $ligne = LigneTarifaire::factory()->create();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.grille-tarifaire.store'), [
        'categorie' => 'Graphisme & identité visuelle',
        'categorie_ordre' => 1,
        'prestation' => 'Flyer',
        'tarif' => '10 000–25 000',
    ])->assertRedirect();
    $this->assertDatabaseHas('lignes_tarifaires', ['prestation' => 'Flyer']);

    $this->actingAs($membre, 'admin')->patch(route('espace-admin.grille-tarifaire.update', $ligne), [
        'prestation' => 'Modifié',
        'tarif' => 'Sur devis',
    ])->assertRedirect();

    $this->actingAs($membre, 'admin')->delete(route('espace-admin.grille-tarifaire.destroy', $ligne))->assertForbidden();
    $this->assertDatabaseHas('lignes_tarifaires', ['id' => $ligne->id]);
});

test('a read-only role cannot create, modify or delete', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $ligne = LigneTarifaire::factory()->create();

    $this->actingAs($juriste, 'admin')->post(route('espace-admin.grille-tarifaire.store'), [
        'categorie' => 'Graphisme & identité visuelle',
        'categorie_ordre' => 1,
        'prestation' => 'Flyer',
        'tarif' => '10 000–25 000',
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')->patch(route('espace-admin.grille-tarifaire.update', $ligne), [
        'prestation' => 'Modifié',
        'tarif' => 'Sur devis',
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')->delete(route('espace-admin.grille-tarifaire.destroy', $ligne))->assertForbidden();
});
