<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\MembreEquipe;

test('a client can reach the espace client but not the other espaces', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-client.dashboard'))->assertOk();
    $this->actingAs($client->user)->get(route('espace-apprenant.dashboard'))->assertForbidden();
    $this->actingAs($client->user)->get(route('espace-admin.dashboard'))->assertForbidden();
});

test('an apprenant can reach only the espace apprenant', function () {
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($apprenant->user)->get(route('espace-apprenant.dashboard'))->assertOk();
    $this->actingAs($apprenant->user)->get(route('espace-client.dashboard'))->assertForbidden();
});

test('a membre equipe can reach only the espace admin, which exposes its droits', function () {
    $membre = MembreEquipe::factory()->juriste()->create();

    $response = $this->actingAs($membre->user)->get(route('espace-admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-dashboard')
        ->where('membre.role', 'JURISTE')
        ->where('droits.CONTRATS_CLAUSES', 'TOTAL')
        ->where('droits.FACTURES_PAIEMENTS', 'AUCUN'),
    );

    $this->actingAs($membre->user)->get(route('espace-client.dashboard'))->assertForbidden();
});

test('a guest is redirected to the login page for any espace', function () {
    $this->get(route('espace-client.dashboard'))->assertRedirect(route('login'));
    $this->get(route('espace-admin.dashboard'))->assertRedirect(route('login'));
});
