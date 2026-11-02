<?php

use App\Enums\MembreEquipeRole;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;
use App\Models\MembreEquipe;

test('a controle de gestion can create an abonnement and then activate it', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $formule = Formule::factory()->create(['nom' => 'Pack Croissance', 'statut' => 'PUBLIEE']);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-abonnements')
            ->has('clients', 1)
            ->has('formules', 1)
            ->has('servicesDisponibles'));

    $this->actingAs($membre, 'admin')->post(route('espace-admin.abonnements.store'), [
        'client_id' => $client->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 45000,
        'date_souscription' => now()->toDateString(),
    ])->assertRedirect();

    $abonnement = Abonnement::where('client_id', $client->id)->firstOrFail();
    expect($abonnement->statut)->toBe('BROUILLON');

    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.abonnements.update', $abonnement), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($abonnement->fresh()->statut)->toBe('ACTIF');
});

test('abonnements index lists the subscriber name for both client and entreprise records', function () {
    $membre = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);
    $formule = Formule::factory()->create();
    Abonnement::create([
        'client_id' => $client->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 45000,
        'date_souscription' => now()->toDateString(),
        'statut' => 'ACTIF',
    ]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('abonnements', 1)
            ->where('abonnements.0.client', 'Aïcha N.'));
});

test('a role without access to abonnements cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.abonnements.index'))
        ->assertForbidden();
});

test('a controle de gestion can create and modify a formule but not delete it', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $formule = Formule::factory()->create();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.formules.store'), [
        'nom' => 'Essentiel',
        'prix_affiche' => '75 000 FCFA / mois',
        'contenu' => 'Pour démarrer sereinement.',
        'services_inclus' => ['Logo et identité visuelle', 'Site vitrine 5 pages'],
        'populaire' => false,
        'statut' => 'BROUILLON',
    ])->assertRedirect();

    $created = Formule::where('nom', 'Essentiel')->firstOrFail();
    expect($created->services_inclus)->toBe(['Logo et identité visuelle', 'Site vitrine 5 pages']);

    $this->actingAs($membre, 'admin')->patch(route('espace-admin.formules.update', $formule), [
        'statut' => 'PUBLIEE',
    ])->assertRedirect();
    expect($formule->fresh()->statut)->toBe('PUBLIEE');

    $this->actingAs($membre, 'admin')->delete(route('espace-admin.formules.destroy', $formule))->assertForbidden();
    $this->assertDatabaseHas('formules', ['id' => $formule->id]);
});

test('a promotrice can delete a formule, which preserves its historical abonnements', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formule = Formule::factory()->create();
    $abonnement = Abonnement::create([
        'client_id' => Client::factory()->create()->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 45000,
        'date_souscription' => now()->toDateString(),
        'statut' => 'ACTIF',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.formules.destroy', $formule))
        ->assertRedirect();

    $this->assertDatabaseMissing('formules', ['id' => $formule->id]);
    $this->assertDatabaseHas('abonnements', ['id' => $abonnement->id, 'formule_id' => null]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('abonnements.0.formule', '—'));
});
