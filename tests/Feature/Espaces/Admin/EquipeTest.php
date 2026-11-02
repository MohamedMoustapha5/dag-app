<?php

use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;

test('the index page exposes the access matrix, confirming the juriste role grants contract management', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $response = $this->actingAs($promotrice->user)->get(route('espace-admin.equipe.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-equipe')
        ->has('matriceRoles', 5)
        ->where('matriceRoles.1.role', 'JURISTE')
        ->where('matriceRoles.1.droits.CONTRATS_CLAUSES', 'TOTAL')
        ->has('domaines', 9));
});

test('a promotrice can create a new membre equipe account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice->user)->post(route('espace-admin.equipe.store'), [
        'name' => 'Pascal E.',
        'email' => 'pascal.e@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'pascal.e@dag.cm', 'type' => 'MEMBRE_EQUIPE']);
    $this->assertDatabaseHas('membres_equipe', ['role' => 'JURISTE', 'statut' => 'ACTIF']);
});

test('creating a membre equipe account rejects an already used email', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $existant = MembreEquipe::factory()->create();

    $this->actingAs($promotrice->user)->post(route('espace-admin.equipe.store'), [
        'name' => 'Doublon',
        'email' => $existant->user->email,
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertSessionHasErrors('email');
});

test('a promotrice can change a member role and deactivate their account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu, 'statut' => 'ACTIF']);

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.equipe.update', $membre), ['role' => MembreEquipeRole::Juriste->value])
        ->assertRedirect();
    expect($membre->fresh()->role)->toBe(MembreEquipeRole::Juriste);

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.equipe.update', $membre), ['statut' => 'INACTIF'])
        ->assertRedirect();
    expect($membre->fresh()->statut)->toBe('INACTIF');

    $this->actingAs($membre->fresh()->user)
        ->get(route('espace-admin.dashboard'))
        ->assertForbidden();
});

test('a promotrice cannot deactivate her own account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice->user)
        ->patch(route('espace-admin.equipe.update', $promotrice), ['statut' => 'INACTIF'])
        ->assertForbidden();

    expect($promotrice->fresh()->statut)->toBe('ACTIF');
});

test('a role other than promotrice cannot manage the team', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $autre = MembreEquipe::factory()->create();

    $this->actingAs($juriste->user)->get(route('espace-admin.equipe.index'))->assertForbidden();

    $this->actingAs($juriste->user)->post(route('espace-admin.equipe.store'), [
        'name' => 'Intrus',
        'email' => 'intrus@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertForbidden();

    $this->actingAs($juriste->user)
        ->patch(route('espace-admin.equipe.update', $autre), ['statut' => 'INACTIF'])
        ->assertForbidden();
});
