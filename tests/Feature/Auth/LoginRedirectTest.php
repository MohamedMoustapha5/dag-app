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

test('an active membre equipe is redirected to the espace admin after login', function () {
    $membre = MembreEquipe::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $membre->user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('espace-admin.dashboard'));
});

test('a deactivated membre equipe cannot log in', function () {
    $membre = MembreEquipe::factory()->inactif()->create();

    $response = $this->post(route('login.store'), [
        'email' => $membre->user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors();
});
