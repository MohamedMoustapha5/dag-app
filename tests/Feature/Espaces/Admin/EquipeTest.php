<?php

use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;

test('the index page exposes the access matrix, confirming the juriste role grants contract management', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.equipe.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-equipe')
        ->has('matriceRoles', 6)
        ->where('matriceRoles.1.role', 'JURISTE')
        ->where('matriceRoles.1.droits.CONTRATS_CLAUSES', 'TOTAL')
        ->has('domaines', 9));
});

test('a promotrice can create a new membre equipe account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Pascal E.',
        'email' => 'pascal.e@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertRedirect();

    $this->assertDatabaseHas('membres_equipe', ['email' => 'pascal.e@dag.cm', 'role' => 'JURISTE', 'statut' => 'ACTIF']);
    $this->assertDatabaseMissing('users', ['email' => 'pascal.e@dag.cm']);
});

test('creating a membre equipe account rejects an already used email', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $existant = MembreEquipe::factory()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Doublon',
        'email' => $existant->email,
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertSessionHasErrors('email');
});

test('a promotrice can change a member role and deactivate their account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu, 'statut' => 'ACTIF']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['role' => MembreEquipeRole::Juriste->value])
        ->assertRedirect();
    expect($membre->fresh()->role)->toBe(MembreEquipeRole::Juriste);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['statut' => 'INACTIF'])
        ->assertRedirect();
    expect($membre->fresh()->statut)->toBe('INACTIF');

    $this->actingAs($membre->fresh(), 'admin')
        ->get(route('espace-admin.dashboard'))
        ->assertForbidden();
});

test('a promotrice cannot deactivate her own account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $promotrice), ['statut' => 'INACTIF'])
        ->assertForbidden();

    expect($promotrice->fresh()->statut)->toBe('ACTIF');
});

test('a role other than promotrice cannot manage the team', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $autre = MembreEquipe::factory()->create();

    $this->actingAs($juriste, 'admin')->get(route('espace-admin.equipe.index'))->assertForbidden();

    $this->actingAs($juriste, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Intrus',
        'email' => 'intrus@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.equipe.update', $autre), ['statut' => 'INACTIF'])
        ->assertForbidden();
});

test('a promotrice cannot change her own role', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $promotrice), ['role' => MembreEquipeRole::Juriste->value])
        ->assertForbidden();

    expect($promotrice->fresh()->role)->toBe(MembreEquipeRole::Promotrice);
});

test('a promotrice can edit what a role may do, and members of that role follow the new rights', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();

    expect($juriste->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();

    $this->actingAs($promotrice, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'JURISTE',
        'donnee' => 'FACTURES_PAIEMENTS',
        'niveau' => 'LECTURE',
    ])->assertRedirect();

    expect($juriste->fresh()->peut(Donnee::FacturesPaiements, Action::Voir))->toBeTrue();

    $this->actingAs($promotrice, 'admin')->get(route('espace-admin.equipe.index'))
        ->assertInertia(fn ($page) => $page
            ->where('matriceRoles.1.droits.FACTURES_PAIEMENTS', 'LECTURE')
            ->where('matriceRoles.1.personnalises', ['FACTURES_PAIEMENTS']));

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.equipe.droits.reset', 'JURISTE'))
        ->assertRedirect();

    expect($juriste->fresh()->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();
});

test("the directrice's own rights cannot be edited and only she can edit rights", function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();

    $this->actingAs($promotrice, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'PROMOTRICE',
        'donnee' => 'CATALOGUE',
        'niveau' => 'AUCUN',
    ])->assertSessionHasErrors('role');

    $this->actingAs($juriste, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'JURISTE',
        'donnee' => 'FACTURES_PAIEMENTS',
        'niveau' => 'TOTAL',
    ])->assertForbidden();
});

test('a promotrice can create a developpeur account, who starts with read-only rights', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Mustapha',
        'email' => 'dev@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Developpeur->value,
    ])->assertRedirect();

    $developpeur = MembreEquipe::where('email', 'dev@dag.cm')->firstOrFail();

    expect($developpeur->role)->toBe(MembreEquipeRole::Developpeur);
    expect($developpeur->role->label())->toBe('Développeur');
    expect($developpeur->peut(Donnee::FacturesPaiements, Action::Voir))->toBeTrue();
    expect($developpeur->peut(Donnee::FacturesPaiements, Action::Modifier))->toBeFalse();
    expect($developpeur->peut(Donnee::ContratsClauses, Action::Telecharger))->toBeTrue();
});

test('the developpeur can open the admin space but cannot manage the team', function () {
    $developpeur = MembreEquipe::factory()->developpeur()->create();

    $this->actingAs($developpeur, 'admin')
        ->get(route('espace-admin.statistiques'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('membre.roleLabel', 'Développeur')->where('onglets', ['indicateurs', 'documents']));

    $this->actingAs($developpeur, 'admin')
        ->get(route('espace-admin.equipe.index'))
        ->assertForbidden();
});
