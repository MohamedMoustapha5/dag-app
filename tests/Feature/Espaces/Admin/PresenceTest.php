<?php

use App\Enums\MembreEquipeRole;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\Presence;
use App\Models\Seance;

function inscrireA(FormationSession $session, string $statut, ?string $nom = null): Inscription
{
    $apprenant = Apprenant::factory()->create();

    if ($nom !== null) {
        $apprenant->user->update(['name' => $nom]);
    }

    return Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => $statut,
    ]);
}

beforeEach(function () {
    $this->travelTo('2026-11-11 10:00:00');
    $this->session = FormationSession::factory()->create(['ville' => 'Douala']);
    $this->seance = Seance::create(['formation_session_id' => $this->session->id, 'titre' => 'Séance 1', 'date' => '2026-11-11']);
});

test('the attendance sheet lists only the apprenants whose seat is confirmed, by name', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $zoe = inscrireA($this->session, 'VALIDEE', 'Zoé Mbarga');
    $alain = inscrireA($this->session, 'FORMEE', 'Alain Ndongo');
    inscrireA($this->session, 'DOSSIER_EN_ETUDE');
    inscrireA($this->session, 'EN_ATTENTE_PAIEMENT');
    Presence::create(['seance_id' => $this->seance->id, 'inscription_id' => $zoe->id, 'statut' => 'RETARD']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.seances.presences.show', $this->seance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-seance-presences')
            ->where('seance.pointable', true)
            ->where('peutPointer', true)
            ->has('apprenants', 2)
            ->where('apprenants.0.inscriptionId', $alain->id)
            ->where('apprenants.0.statut', null)
            ->where('apprenants.1.inscriptionId', $zoe->id)
            ->where('apprenants.1.statut', 'RETARD'));
});

test('the team takes attendance: late counts as present, excused as absent, and who took it is recorded', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $retard = inscrireA($this->session, 'VALIDEE');
    $excuse = inscrireA($this->session, 'VALIDEE');

    $this->actingAs($promotrice, 'admin')
        ->put(route('espace-admin.seances.presences.update', $this->seance), [
            'presences' => [
                ['inscription_id' => $retard->id, 'statut' => 'RETARD', 'commentaire' => '  20 minutes  '],
                ['inscription_id' => $excuse->id, 'statut' => 'EXCUSE', 'commentaire' => 'Certificat médical'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $presenceRetard = Presence::where('inscription_id', $retard->id)->sole();
    expect($presenceRetard->present)->toBeTrue()
        ->and($presenceRetard->commentaire)->toBe('20 minutes')
        ->and($presenceRetard->pointee_par)->toBe($promotrice->id)
        ->and($presenceRetard->pointee_at)->not->toBeNull();
    expect(Presence::where('inscription_id', $excuse->id)->sole()->present)->toBeFalse();
    expect(ActivityLog::where('action', 'a pointé la présence')->where('sujet_id', $this->seance->id)->exists())->toBeTrue();
});

test('taking attendance again corrects the same records instead of adding new ones', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireA($this->session, 'VALIDEE');
    Presence::create(['seance_id' => $this->seance->id, 'inscription_id' => $inscription->id, 'statut' => 'ABSENT']);

    $this->actingAs($promotrice, 'admin')
        ->put(route('espace-admin.seances.presences.update', $this->seance), [
            'presences' => [['inscription_id' => $inscription->id, 'statut' => 'PRESENT']],
        ])
        ->assertRedirect();

    $presence = Presence::where('inscription_id', $inscription->id)->sole();
    expect($presence->statut)->toBe('PRESENT')
        ->and($presence->present)->toBeTrue();
});

test('attendance cannot be taken before the séance takes place', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireA($this->session, 'VALIDEE');
    $demain = Seance::create(['formation_session_id' => $this->session->id, 'date' => '2026-11-12']);

    $this->actingAs($promotrice, 'admin')
        ->put(route('espace-admin.seances.presences.update', $demain), [
            'presences' => [['inscription_id' => $inscription->id, 'statut' => 'PRESENT']],
        ])
        ->assertSessionHasErrors(['presences' => "Cette séance n'a pas encore eu lieu : la présence se pointe le jour même ou après."]);

    expect(Presence::count())->toBe(0);
});

test('an apprenant who is not on the sheet of the session cannot be marked', function (string $statut, bool $autreSession) {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireA($autreSession ? FormationSession::factory()->create() : $this->session, $statut);

    $this->actingAs($promotrice, 'admin')
        ->put(route('espace-admin.seances.presences.update', $this->seance), [
            'presences' => [['inscription_id' => $inscription->id, 'statut' => 'PRESENT']],
        ])
        ->assertSessionHasErrors(['presences.0.inscription_id' => "Cet apprenant n'est pas sur la feuille de présence de la session."]);

    expect(Presence::count())->toBe(0);
})->with([
    'seat not confirmed yet' => ['EN_ATTENTE_PAIEMENT', false],
    'another session' => ['VALIDEE', true],
]);

test('an unknown attendance status is refused', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireA($this->session, 'VALIDEE');

    $this->actingAs($promotrice, 'admin')
        ->put(route('espace-admin.seances.presences.update', $this->seance), [
            'presences' => [['inscription_id' => $inscription->id, 'statut' => 'MALADE']],
        ])
        ->assertSessionHasErrors('presences.0.statut');
});

test('a role with read access sees the sheet but cannot take attendance', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $inscription = inscrireA($this->session, 'VALIDEE');

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.seances.presences.show', $this->seance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('peutPointer', false));

    $this->actingAs($juriste, 'admin')
        ->put(route('espace-admin.seances.presences.update', $this->seance), [
            'presences' => [['inscription_id' => $inscription->id, 'statut' => 'PRESENT']],
        ])
        ->assertForbidden();

    expect(Presence::count())->toBe(0);
});

test('the session page shows which séances are still to take and the assiduity of each apprenant', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $assidu = inscrireA($this->session, 'VALIDEE', 'Alain Ndongo');
    $absent = inscrireA($this->session, 'VALIDEE', 'Zoé Mbarga');
    $passee = Seance::create(['formation_session_id' => $this->session->id, 'titre' => 'Ouverture', 'date' => '2026-11-04']);
    $future = Seance::create(['formation_session_id' => $this->session->id, 'titre' => 'Clôture', 'date' => '2026-11-18']);
    Presence::create(['seance_id' => $future->id, 'inscription_id' => $absent->id, 'statut' => 'PRESENT']);
    Presence::create(['seance_id' => $passee->id, 'inscription_id' => $assidu->id, 'statut' => 'PRESENT']);
    Presence::create(['seance_id' => $passee->id, 'inscription_id' => $absent->id, 'statut' => 'ABSENT']);
    Presence::create(['seance_id' => $this->seance->id, 'inscription_id' => $assidu->id, 'statut' => 'RETARD']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.sessions.show', $this->session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seances.0.presence.etat', 'pointee')
            ->where('seances.0.presence.presents', 1)
            ->where('seances.1.presence.etat', 'incomplete')
            ->where('seances.2.presence.etat', 'a_venir')
            ->where('assiduite.0.inscriptionId', $assidu->id)
            ->where('assiduite.0.taux', 100)
            ->where('assiduite.1.inscriptionId', $absent->id)
            ->where('assiduite.1.pointees', 1)
            ->where('assiduite.1.taux', 0));
});

test('the attendance of a session is exported as a spreadsheet', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireA($this->session, 'VALIDEE', '=Alain Ndongo');
    Presence::create(['seance_id' => $this->seance->id, 'inscription_id' => $inscription->id, 'statut' => 'RETARD']);

    $csv = $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.sessions.presences.export', $this->session))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Séance 1 (11/11/2026)')
        ->toContain("'=Alain Ndongo")
        ->toContain('En retard')
        ->toContain('100 %');
});

test('a role without download access cannot export the attendance', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.sessions.presences.export', $this->session))
        ->assertForbidden();
});

test('the apprenant sees a late arrival and an excused absence in their progression', function () {
    $inscription = inscrireA($this->session, 'VALIDEE');
    $suivante = Seance::create(['formation_session_id' => $this->session->id, 'date' => '2026-11-12']);
    Presence::create(['seance_id' => $this->seance->id, 'inscription_id' => $inscription->id, 'statut' => 'RETARD']);
    Presence::create(['seance_id' => $suivante->id, 'inscription_id' => $inscription->id, 'statut' => 'EXCUSE']);

    $this->actingAs($inscription->apprenant->user)
        ->get(route('espace-apprenant.progression.show', $inscription))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('progression', 50)
            ->where('seances.0.present', true)
            ->where('seances.0.statutPresence', 'RETARD')
            ->where('seances.1.present', false)
            ->where('seances.1.statutPresence', 'EXCUSE'));
});

test('the formations page flags the sessions with séances held but not yet pointed', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    inscrireA($this->session, 'VALIDEE');
    Seance::create(['formation_session_id' => $this->session->id, 'date' => '2026-11-04']);
    Seance::create(['formation_session_id' => $this->session->id, 'date' => '2026-11-18']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.id', $this->session->id)
            ->where('sessions.0.seancesAPointer', 2));
});
