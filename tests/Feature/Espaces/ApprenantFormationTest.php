<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;

test('the apprenant formations page lists open sessions without leaving the espace', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);
    $openSession = FormationSession::factory()->for($formation)->create(['statut' => 'OUVERTE']);
    FormationSession::factory()->for($formation)->create(['statut' => 'BROUILLON']);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.formations'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/formations-index')
        ->where('apprenant.nom', $apprenant->user->name)
        ->has('sessions', 1)
        ->where('sessions.0.sessionId', $openSession->id));
});

test('a client cannot reach the apprenant formations page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.formations'))->assertForbidden();
});

test('a guest is redirected to the login page for the apprenant formations route', function () {
    $this->get(route('espace-apprenant.formations'))->assertRedirect(route('login'));
});
