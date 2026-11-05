<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\Presence;
use App\Models\Seance;

test('the roster endpoint lists a session inscriptions with their presence count', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create(['ville' => 'Douala']);
    $apprenant = Apprenant::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);
    $seance1 = Seance::create(['formation_session_id' => $session->id, 'date' => '2027-01-10']);
    $seance2 = Seance::create(['formation_session_id' => $session->id, 'date' => '2027-01-17']);
    Presence::create(['seance_id' => $seance1->id, 'inscription_id' => $inscription->id, 'present' => true]);
    Presence::create(['seance_id' => $seance2->id, 'inscription_id' => $inscription->id, 'present' => false]);

    $response = $this->actingAs($promotrice, 'admin')->getJson(route('espace-admin.sessions.inscrits', $session));

    $response->assertOk();
    $response->assertJson([
        'session' => ['ville' => 'Douala'],
        'peutValider' => true,
        'inscriptions' => [
            [
                'id' => $inscription->id,
                'nom' => $apprenant->user->name,
                'statut' => 'VALIDEE',
                'presence' => 1,
                'seancesTotal' => 2,
            ],
        ],
    ]);
});

test('a promotrice can validate a pending payment, which the refuse action cannot touch afterwards', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertOk()
        ->assertJson(['statut' => 'VALIDEE']);

    expect($inscription->fresh()->statut)->toBe('VALIDEE');

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription))
        ->assertStatus(409);
});

test('a promotrice can refuse a pending payment', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription))
        ->assertOk()
        ->assertJson(['statut' => 'ABANDONNEE']);

    expect($inscription->fresh()->statut)->toBe('ABANDONNEE');
});

test('a role without the validation level cannot validate or refuse a payment', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $this->actingAs($membre, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertForbidden();

    $this->actingAs($membre, 'admin')
        ->getJson(route('espace-admin.sessions.inscrits', $session))
        ->assertOk()
        ->assertJson(['peutValider' => false]);
});

test('exporting the roster streams a csv file', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.sessions.export', $session));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('a name typed as a spreadsheet formula is exported as plain text', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $apprenant->user->update(['name' => '=HYPERLINK("http://evil.example","Clic")']);
    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $csv = $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.sessions.export', $session))
        ->streamedContent();

    expect($csv)->toContain('"\'=HYPERLINK(')
        ->not->toContain("\n\"=HYPERLINK(");
});
