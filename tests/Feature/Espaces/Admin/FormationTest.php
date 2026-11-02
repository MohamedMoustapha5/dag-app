<?php

use App\Enums\MembreEquipeRole;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\MembreEquipe;
use App\Models\Seance;

test('a promotrice can quick-create a session, which creates its formation on first use', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.formations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-formations')
            ->has('sessions', 0));

    $this->actingAs($promotrice->user)->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo — Initiation',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 15,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    $formation = Formation::where('nom', 'Montage vidéo — Initiation')->firstOrFail();
    $session = FormationSession::where('formation_id', $formation->id)->firstOrFail();
    expect($session->ville)->toBe('Douala');
    expect($session->statut)->toBe('OUVERTE');

    // Submitting the same title again reuses the formation instead of
    // creating a duplicate catalog entry.
    $this->actingAs($promotrice->user)->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo — Initiation',
        'ville' => 'Yaoundé',
        'mode' => 'EN_LIGNE',
        'places' => 10,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    expect(Formation::where('nom', 'Montage vidéo — Initiation')->count())->toBe(1);
    expect(FormationSession::where('formation_id', $formation->id)->count())->toBe(2);

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.formations.index'))
        ->assertInertia(fn ($page) => $page->has('sessions', 2));
});

test('a promotrice can add a session to an existing formation and manage its seances', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create();

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.formations.show', $formation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-formation-show')
            ->has('sessions', 0));

    $this->actingAs($promotrice->user)->post(route('espace-admin.formations.sessions.store', $formation), [
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 20,
    ])->assertRedirect();

    $session = FormationSession::where('formation_id', $formation->id)->firstOrFail();
    expect($session->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-session-show')
            ->where('session.ville', 'Douala'));

    $this->actingAs($promotrice->user)->post(route('espace-admin.sessions.seances.store', $session), [
        'titre' => 'Séance 1',
        'date' => '2027-01-10',
    ])->assertRedirect();

    $this->assertDatabaseHas('seances', ['formation_session_id' => $session->id, 'titre' => 'Séance 1']);
});

test('a promotrice can publish a formation and open a session', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create(['statut' => 'BROUILLON']);
    $session = FormationSession::factory()->for($formation)->create(['statut' => 'BROUILLON']);

    $this->actingAs($promotrice->user)->patch(route('espace-admin.formations.update', $formation), [
        'nom' => $formation->nom,
        'description' => $formation->description,
        'statut' => 'PUBLIEE',
    ])->assertRedirect();
    expect($formation->fresh()->statut)->toBe('PUBLIEE');

    $this->actingAs($promotrice->user)->patch(route('espace-admin.sessions.update', $session), [
        'ville' => $session->ville,
        'mode' => $session->mode,
        'places' => $session->places,
        'statut' => 'OUVERTE',
    ])->assertRedirect();
    expect($session->fresh()->statut)->toBe('OUVERTE');
});

test('a promotrice can delete a session, which also removes its seances', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2027-01-10']);

    $this->actingAs($promotrice->user)
        ->delete(route('espace-admin.sessions.destroy', $session))
        ->assertRedirect();

    $this->assertDatabaseMissing('formation_sessions', ['id' => $session->id]);
    $this->assertDatabaseMissing('seances', ['formation_session_id' => $session->id]);
});

test('a promotrice can delete a seance', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $seance = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2027-01-10']);

    $this->actingAs($promotrice->user)
        ->delete(route('espace-admin.seances.destroy', $seance))
        ->assertRedirect();

    $this->assertDatabaseMissing('seances', ['id' => $seance->id]);
});

test('a role without creer access cannot quick-create a session or delete one', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $session = FormationSession::factory()->create();

    $this->actingAs($membre->user)
        ->get(route('espace-admin.formations.index'))
        ->assertOk();

    $this->actingAs($membre->user)->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Test',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 10,
        'statut' => 'OUVERTE',
    ])->assertForbidden();

    $this->actingAs($membre->user)
        ->delete(route('espace-admin.sessions.destroy', $session))
        ->assertForbidden();
});
