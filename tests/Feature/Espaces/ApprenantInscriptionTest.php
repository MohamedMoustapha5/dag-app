<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;

test('an in-progress inscription exposes its attendance progression', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create(['ville' => 'Douala']);

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $seance1 = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Introduction', 'date' => now()->subDays(5)]);
    $seance2 = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Pratique', 'date' => now()->subDays(2)]);

    Presence::create(['seance_id' => $seance1->id, 'inscription_id' => $inscription->id, 'present' => true]);
    Presence::create(['seance_id' => $seance2->id, 'inscription_id' => $inscription->id, 'present' => false]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.inscriptions'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/inscriptions-index')
        ->has('inscriptions', 1)
        ->where('inscriptions.0.formation', 'Community Management')
        ->where('inscriptions.0.statut', 'VALIDEE')
        ->where('inscriptions.0.progression', 50)
        ->where('inscriptions.0.semainesAvantDebut', null));
});

test('an apprenant following two formations at once gets a distinct progression per inscription', function () {
    $apprenant = Apprenant::factory()->create();

    $formationA = Formation::factory()->create(['nom' => 'Community Management']);
    $sessionA = FormationSession::factory()->for($formationA)->create();
    $inscriptionA = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formationA->id,
        'formation_session_id' => $sessionA->id,
        'statut' => 'VALIDEE',
    ]);
    $seanceA = Seance::create(['formation_session_id' => $sessionA->id, 'titre' => 'Introduction', 'date' => now()->subDay()]);
    Presence::create(['seance_id' => $seanceA->id, 'inscription_id' => $inscriptionA->id, 'present' => true]);

    $formationB = Formation::factory()->create(['nom' => 'Création de contenu']);
    $sessionB = FormationSession::factory()->for($formationB)->create();
    $inscriptionB = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formationB->id,
        'formation_session_id' => $sessionB->id,
        'statut' => 'VALIDEE',
    ]);
    $seanceB = Seance::create(['formation_session_id' => $sessionB->id, 'titre' => 'Introduction', 'date' => now()->subDay()]);
    Presence::create(['seance_id' => $seanceB->id, 'inscription_id' => $inscriptionB->id, 'present' => false]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.inscriptions'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/inscriptions-index')
        ->has('inscriptions', 2)
        ->where('inscriptions', function ($inscriptions) use ($inscriptionA, $inscriptionB) {
            $byId = collect($inscriptions)->keyBy('id');

            return $byId[$inscriptionA->id]['progression'] === 100
                && $byId[$inscriptionB->id]['progression'] === 0;
        }));
});

test('an inscription for a future session reports the weeks remaining before it starts', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create([
        'date_debut' => now()->addWeeks(3)->toDateString(),
    ]);

    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.inscriptions'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/inscriptions-index')
        ->where('inscriptions.0.semainesAvantDebut', 3));
});

test('a client cannot reach the apprenant inscriptions page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.inscriptions'))->assertForbidden();
});
