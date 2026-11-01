<?php

use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;

test('the apprenant dashboard exposes stats computed from the authenticated apprenant own records', function () {
    $apprenant = Apprenant::factory()->create();

    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $seancePassee = Seance::create([
        'formation_session_id' => $session->id,
        'titre' => 'Introduction',
        'date' => now()->subDays(3),
    ]);
    $seanceAVenir1 = Seance::create([
        'formation_session_id' => $session->id,
        'titre' => 'Pratique',
        'date' => now()->addDays(2),
    ]);
    $seanceAVenir2 = Seance::create([
        'formation_session_id' => $session->id,
        'titre' => 'Évaluation',
        'date' => now()->addDays(5),
    ]);

    Presence::create([
        'seance_id' => $seancePassee->id,
        'inscription_id' => $inscription->id,
        'present' => true,
    ]);
    Presence::create([
        'seance_id' => $seanceAVenir1->id,
        'inscription_id' => $inscription->id,
        'present' => false,
    ]);

    Candidature::factory()->for($apprenant)->create();
    Candidature::factory()->for($apprenant)->create();

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant-dashboard')
        ->where('apprenant.nom', $apprenant->user->name)
        ->where('stats.formationEnCours', 'Community Management')
        ->where('stats.progression', 33)
        ->where('stats.candidaturesEnvoyees', 2)
        ->where('stats.seancesRestantes', 2));
});

test('the apprenant dashboard reports no active formation when none is in progress', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant-dashboard')
        ->where('stats.formationEnCours', null)
        ->where('stats.progression', 0)
        ->where('stats.seancesRestantes', 0));
});
