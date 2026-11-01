<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;

test('the progression overview lists every formation the apprenant is following', function () {
    $apprenant = Apprenant::factory()->create();

    $formationA = Formation::factory()->create(['nom' => 'Community Management']);
    $sessionA = FormationSession::factory()->for($formationA)->create();
    $inscriptionA = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formationA->id,
        'formation_session_id' => $sessionA->id,
        'statut' => 'VALIDEE',
    ]);
    $seanceA = Seance::create(['formation_session_id' => $sessionA->id, 'titre' => 'Introduction', 'date' => '2026-11-01']);
    Presence::create(['seance_id' => $seanceA->id, 'inscription_id' => $inscriptionA->id, 'present' => true]);

    $formationB = Formation::factory()->create(['nom' => 'Création de contenu']);
    $sessionB = FormationSession::factory()->for($formationB)->create();
    $inscriptionB = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formationB->id,
        'formation_session_id' => $sessionB->id,
        'statut' => 'VALIDEE',
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.progression'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/progression-index')
        ->has('formations', 2)
        ->where('formations', function ($formations) use ($inscriptionA, $inscriptionB) {
            $byId = collect($formations)->keyBy('inscriptionId');

            return $byId->has($inscriptionA->id)
                && $byId->has($inscriptionB->id)
                && $byId[$inscriptionA->id]['progression'] === 100
                && $byId[$inscriptionB->id]['progression'] === 0;
        }));
});

test('the progression overview reports no formations when none is active', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.progression'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/progression-index')
        ->has('formations', 0));
});

test('the progression detail page reports seances and attendance for the given inscription', function () {
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
    $seance3 = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Évaluation', 'date' => '2026-11-15']);

    Presence::create(['seance_id' => $seance1->id, 'inscription_id' => $inscription->id, 'present' => true]);
    Presence::create(['seance_id' => $seance2->id, 'inscription_id' => $inscription->id, 'present' => false]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.progression.show', $inscription));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/progression-show')
        ->where('formation', 'Community Management')
        ->where('progression', 33)
        ->where('stats.suivies', 1)
        ->where('stats.manquees', 1)
        ->where('stats.aVenir', 1)
        ->has('seances', 3)
        ->where('seances.0.present', true)
        ->where('seances.1.present', false)
        ->where('seances.2.present', null));
});

test('an apprenant cannot view the progression detail of another apprenant inscription', function () {
    $apprenant = Apprenant::factory()->create();
    $autre = Apprenant::factory()->create();
    $formation = Formation::factory()->create();

    $inscription = Inscription::create([
        'apprenant_id' => $autre->id,
        'formation_id' => $formation->id,
        'statut' => 'VALIDEE',
    ]);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.progression.show', $inscription))
        ->assertForbidden();
});

test('a client cannot reach the apprenant progression pages', function () {
    $client = Client::factory()->create();
    $formation = Formation::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => $formation->id,
        'statut' => 'VALIDEE',
    ]);

    $this->actingAs($client->user)->get(route('espace-apprenant.progression'))->assertForbidden();
    $this->actingAs($client->user)->get(route('espace-apprenant.progression.show', $inscription))->assertForbidden();
});
