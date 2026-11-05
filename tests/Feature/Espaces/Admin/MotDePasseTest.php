<?php

use App\Models\MembreEquipe;
use Illuminate\Support\Facades\Hash;

test('a team member changes their own password with the current one', function () {
    $membre = MembreEquipe::factory()->create();

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.mot-de-passe'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('espaces/admin-mot-de-passe'));

    $this->actingAs($membre, 'admin')
        ->put(route('espace-admin.mot-de-passe.update'), [
            'current_password' => 'password',
            'password' => 'Nouveau-mot-de-passe-1',
            'password_confirmation' => 'Nouveau-mot-de-passe-1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('espace-admin.mot-de-passe'));

    expect(Hash::check('Nouveau-mot-de-passe-1', $membre->fresh()->password))->toBeTrue();
});

test('the password stays unchanged when the current one is wrong', function () {
    $membre = MembreEquipe::factory()->create();

    $this->actingAs($membre, 'admin')
        ->put(route('espace-admin.mot-de-passe.update'), [
            'current_password' => 'mauvais',
            'password' => 'Nouveau-mot-de-passe-1',
            'password_confirmation' => 'Nouveau-mot-de-passe-1',
        ])
        ->assertSessionHasErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);

    expect(Hash::check('password', $membre->fresh()->password))->toBeTrue();
});

test('another session of the member is signed out once the password changed', function () {
    $membre = MembreEquipe::factory()->create();

    $this->actingAs($membre, 'admin')->get(route('espace-admin.dashboard'))->assertOk();

    $membre->update(['password' => 'Change-ailleurs-1']);

    $this->get(route('espace-admin.dashboard'))->assertRedirect(route('admin.connexion'));
    $this->assertGuest('admin');
});

test('a guest cannot reach the password page', function () {
    $this->get(route('espace-admin.mot-de-passe'))->assertRedirect(route('admin.connexion'));
});
