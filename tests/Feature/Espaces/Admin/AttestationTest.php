<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\Seance;

test('a promotrice sees formations awaiting attestation validation', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'FORMEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.attestations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-attestations')
        ->has('enAttente', 1)
        ->where('enAttente.0.inscriptionId', $inscription->id)
        ->has('validees', 0));
});

test('a formation still in progress does not appear as awaiting attestation validation', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();

    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => now()->addWeek()->toDateString()]);

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.attestations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('enAttente', 0));
});

test('a promotrice can validate an attestation, which then moves out of the pending list', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'FORMEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertRedirect();

    $this->assertDatabaseHas('attestations', [
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
    ]);

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.attestations.index'));
    $response->assertInertia(fn ($page) => $page
        ->has('enAttente', 0)
        ->has('validees', 1));
});

test('validating the same formation twice is rejected', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'FORMEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    Attestation::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'date_emission' => now()->toDateString(),
        'fichier_pdf' => 'attestations/deja-la.pdf',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertStatus(409);

    expect(Attestation::where('apprenant_id', $apprenant->id)->count())->toBe(1);
});

test('a role without the validation right cannot see or validate attestations', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'FORMEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    $this->actingAs($membre, 'admin')->get(route('espace-admin.attestations.index'))->assertForbidden();

    $this->actingAs($membre, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertForbidden();

    $this->assertDatabaseMissing('attestations', ['apprenant_id' => $apprenant->id]);
});

test('validating an attestation records who validated it and the pdf is then downloadable', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'FORMEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertRedirect();

    $attestation = Attestation::firstOrFail();
    expect($attestation->valide_par_id)->toBe($promotrice->id);

    $response = $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.attestations.pdf', $attestation))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('an apprenant can download only their own validated attestation', function () {
    $apprenant = Apprenant::factory()->create();
    $autre = Apprenant::factory()->create();
    $attestation = Attestation::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => Formation::factory()->create()->id,
        'date_emission' => now()->toDateString(),
    ]);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.attestations.pdf', $attestation))
        ->assertOk();

    $this->actingAs($autre->user)
        ->get(route('espace-apprenant.attestations.pdf', $attestation))
        ->assertNotFound();
});

test('no attestation can be issued for an enrolment that is not completed', function (string $statut, string $dateSeance) {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create();
    $session = FormationSession::factory()->for($formation)->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => $statut,
    ]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => $dateSeance]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertStatus(409);

    expect(Attestation::count())->toBe(0);
})->with([
    'abandoned enrolment' => ['ABANDONNEE', '2026-01-10'],
    'awaiting payment' => ['EN_ATTENTE_PAIEMENT', '2026-01-10'],
    'formation still running' => ['VALIDEE', '2099-01-10'],
]);
