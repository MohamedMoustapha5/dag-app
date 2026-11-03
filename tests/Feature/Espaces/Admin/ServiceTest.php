<?php

use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use App\Models\Service;

test('the index page exposes the actions allowed for the current role', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();

    $response = $this->actingAs($juriste, 'admin')->get(route('espace-admin.services.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-services')
        ->where('actions.creer', false)
        ->where('actions.modifier', false)
        ->where('actions.supprimer', false)
        ->where('droits.CATALOGUE', 'LECTURE')
        ->where('droitsResume', 'Visible par : Directrice (total), Responsable Marketing & Création de contenu (gestion), Juriste, Secrétaire & Développeur (lecture).'));
});

test('a service can use any fournisseur name, not just DAG or Nova-Tech', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.services.store'), [
        'nom' => 'Audit cybersécurité',
        'description' => 'Audit complet de la sécurité informatique.',
        'categorie' => 'Technique & digital',
        'fournisseur' => 'SecureNet Cameroun',
        'statut' => 'PUBLIE',
    ])->assertRedirect();

    $this->assertDatabaseHas('services', ['nom' => 'Audit cybersécurité', 'fournisseur' => 'SecureNet Cameroun']);
});

test('a promotrice can create, modify and delete a service', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $service = Service::factory()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.services.store'), [
        'nom' => 'Création de logo',
        'description' => 'Un logo moderne et professionnel.',
        'categorie' => 'Graphisme & identité visuelle',
        'fournisseur' => 'DAG',
        'statut' => 'BROUILLON',
    ])->assertRedirect();
    $this->assertDatabaseHas('services', ['nom' => 'Création de logo', 'fournisseur' => 'DAG']);

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.services.update', $service), [
        'statut' => 'PUBLIE',
    ])->assertRedirect();
    expect($service->fresh()->statut)->toBe('PUBLIE');

    $this->actingAs($promotrice, 'admin')->delete(route('espace-admin.services.destroy', $service))->assertRedirect();
    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

test('a role with gestion can create and modify but not delete', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $service = Service::factory()->create();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.services.store'), [
        'nom' => 'Création de flyer',
        'description' => 'Flyer A5 recto-verso.',
        'categorie' => 'Graphisme & identité visuelle',
        'fournisseur' => 'DAG',
        'statut' => 'BROUILLON',
    ])->assertRedirect();
    $this->assertDatabaseHas('services', ['nom' => 'Création de flyer']);

    $this->actingAs($membre, 'admin')->patch(route('espace-admin.services.update', $service), [
        'statut' => 'PUBLIE',
    ])->assertRedirect();

    $this->actingAs($membre, 'admin')->delete(route('espace-admin.services.destroy', $service))->assertForbidden();
    $this->assertDatabaseHas('services', ['id' => $service->id]);
});

test('a read-only role cannot create, modify or delete', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $service = Service::factory()->create();

    $this->actingAs($juriste, 'admin')->post(route('espace-admin.services.store'), [
        'nom' => 'Création de flyer',
        'description' => 'Flyer A5 recto-verso.',
        'categorie' => 'Graphisme & identité visuelle',
        'fournisseur' => 'DAG',
        'statut' => 'BROUILLON',
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')->patch(route('espace-admin.services.update', $service), [
        'statut' => 'PUBLIE',
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')->delete(route('espace-admin.services.destroy', $service))->assertForbidden();
});
