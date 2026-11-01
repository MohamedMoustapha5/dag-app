<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;

test('the progression page reports seances and attendance for the active formation', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $seance1 = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Introduction', 'date' => '2026-11-01']);
    $seance2 = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Pratique', 'date' => '2026-11-08']);

    Presence::create(['seance_id' => $seance1->id, 'inscription_id' => $inscription->id, 'present' => true]);
    Presence::create(['seance_id' => $seance2->id, 'inscription_id' => $inscription->id, 'present' => false]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.progression'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/progression')
        ->where('formation', 'Community Management')
        ->where('progression', 50)
        ->has('seances', 2)
        ->where('seances.0.present', true)
        ->where('seances.1.present', false));
});

test('the progression page reports no formation when none is active', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.progression'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/progression')
        ->where('formation', null)
        ->where('progression', 0)
        ->has('seances', 0));
});

test('a client cannot reach the apprenant progression page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.progression'))->assertForbidden();
});
