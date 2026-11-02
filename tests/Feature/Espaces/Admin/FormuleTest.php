<?php

use App\Enums\MembreEquipeRole;
use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Formule;
use App\Models\MembreEquipe;

test('a controle de gestion can create and modify a formule but not delete it', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $formule = Formule::factory()->create();

    $this->actingAs($membre->user)->post(route('espace-admin.formules.store'), [
        'nom' => 'Essentiel',
        'prix_affiche' => '75 000 FCFA / mois',
        'contenu' => 'Pour démarrer sereinement.',
        'services_inclus' => "Logo et identité visuelle\nSite vitrine 5 pages",
        'populaire' => false,
        'statut' => 'BROUILLON',
    ])->assertRedirect();

    $created = Formule::where('nom', 'Essentiel')->firstOrFail();
    expect($created->services_inclus)->toBe(['Logo et identité visuelle', 'Site vitrine 5 pages']);

    $this->actingAs($membre->user)->patch(route('espace-admin.formules.update', $formule), [
        'statut' => 'PUBLIEE',
    ])->assertRedirect();
    expect($formule->fresh()->statut)->toBe('PUBLIEE');

    $this->actingAs($membre->user)->delete(route('espace-admin.formules.destroy', $formule))->assertForbidden();
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

    $this->actingAs($promotrice->user)
        ->delete(route('espace-admin.formules.destroy', $formule))
        ->assertRedirect();

    $this->assertDatabaseMissing('formules', ['id' => $formule->id]);
    $this->assertDatabaseHas('abonnements', ['id' => $abonnement->id, 'formule_id' => null]);

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.abonnements.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('abonnements.0.formule', '—'));
});

test('a role without access to abonnements cannot reach the formules page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.formules.index'))
        ->assertForbidden();
});
