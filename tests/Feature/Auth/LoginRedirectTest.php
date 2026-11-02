<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\MembreEquipe;

test('a client is redirected to the espace client after login', function () {
    $client = Client::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $client->user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('espace-client.dashboard'));
});

test('an apprenant is redirected to the espace apprenant after login', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $apprenant->user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('espace-apprenant.dashboard'));
});

test('an active membre equipe signs in on the admin login and lands on the espace admin', function () {
    $membre = MembreEquipe::factory()->create();

    $this->post(route('admin.connexion.store'), [
        'email' => $membre->email,
        'password' => 'password',
    ])->assertRedirect(route('espace-admin.dashboard'));

    $this->assertAuthenticatedAs($membre, 'admin');
});

test('a membre equipe cannot sign in through the public login', function () {
    $membre = MembreEquipe::factory()->create();

    $this->post(route('login.store'), [
        'email' => $membre->email,
        'password' => 'password',
    ])->assertSessionHasErrors();

    $this->assertGuest();
    $this->assertGuest('admin');
});

test('a client cannot sign in through the admin login', function () {
    $client = Client::factory()->create();

    $this->post(route('admin.connexion.store'), [
        'email' => $client->user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('a deactivated membre equipe cannot log in', function () {
    $membre = MembreEquipe::factory()->inactif()->create();

    $this->post(route('admin.connexion.store'), [
        'email' => $membre->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('a membre equipe can log out of the admin', function () {
    $membre = MembreEquipe::factory()->create();

    $this->actingAs($membre, 'admin')
        ->post(route('admin.deconnexion'))
        ->assertRedirect(route('admin.connexion'));

    $this->assertGuest('admin');
});
