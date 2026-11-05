<?php

use App\Enums\MembreEquipeRole;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\MembreEquipe;
use App\Models\Seance;

test('a promotrice can quick-create a session, which creates its formation on first use', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-formations')
            ->has('sessions', 0));

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo — Initiation',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 15,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    $formation = Formation::where('nom', 'Montage vidéo — Initiation')->firstOrFail();
    $session = FormationSession::where('formation_id', $formation->id)->firstOrFail();
    expect($session->ville)->toBe('Douala');
    expect($session->statut)->toBe('OUVERTE');

    // Submitting the same title again reuses the formation instead of
    // creating a duplicate catalog entry.
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo — Initiation',
        'ville' => 'Yaoundé',
        'mode' => 'EN_LIGNE',
        'places' => 10,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    expect(Formation::where('nom', 'Montage vidéo — Initiation')->count())->toBe(1);
    expect(FormationSession::where('formation_id', $formation->id)->count())->toBe(2);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.index'))
        ->assertInertia(fn ($page) => $page->has('sessions', 2));
});

test('a promotrice can add a session to an existing formation and manage its seances', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.show', $formation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-formation-show')
            ->has('sessions', 0));

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.formations.sessions.store', $formation), [
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 20,
        'prix' => 75000,
    ])->assertRedirect();

    $session = FormationSession::where('formation_id', $formation->id)->firstOrFail();
    expect($session->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-session-show')
            ->where('session.ville', 'Douala'));

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.seances.store', $session), [
        'titre' => 'Séance 1',
        'date' => '2027-01-10',
    ])->assertRedirect();

    $this->assertDatabaseHas('seances', ['formation_session_id' => $session->id, 'titre' => 'Séance 1']);
});

test('a promotrice can publish a formation and open a session', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create(['statut' => 'BROUILLON']);
    $session = FormationSession::factory()->for($formation)->create(['statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.formations.update', $formation), [
        'nom' => $formation->nom,
        'description' => $formation->description,
        'statut' => 'PUBLIEE',
    ])->assertRedirect();
    expect($formation->fresh()->statut)->toBe('PUBLIEE');

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.sessions.update', $session), [
        'ville' => $session->ville,
        'mode' => $session->mode,
        'places' => $session->places,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();
    expect($session->fresh()->statut)->toBe('OUVERTE');
});

test('a promotrice can delete a session, which also removes its seances', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2027-01-10']);

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.sessions.destroy', $session))
        ->assertRedirect();

    $this->assertDatabaseMissing('formation_sessions', ['id' => $session->id]);
    $this->assertDatabaseMissing('seances', ['formation_session_id' => $session->id]);
});

test('a promotrice can delete a seance', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $seance = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2027-01-10']);

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.seances.destroy', $seance))
        ->assertRedirect();

    $this->assertDatabaseMissing('seances', ['id' => $seance->id]);
});

test('a role without creer access cannot quick-create a session or delete one', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $session = FormationSession::factory()->create();

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.formations.index'))
        ->assertOk();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Test',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 10,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertForbidden();

    $this->actingAs($membre, 'admin')
        ->delete(route('espace-admin.sessions.destroy', $session))
        ->assertForbidden();
});

test('creating a session also records the content shown on the public formation page', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Community Management — Fondamentaux',
        'categorie' => 'Community management',
        'description' => 'Apprenez à planifier, publier et modérer.',
        'programme' => "Semaine 1 — Fondamentaux\nSemaine 2 — Création de contenu",
        'duree' => '3 semaines',
        'prerequis' => 'Aucun — niveau débutant',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 15,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    $formation = Formation::where('nom', 'Community Management — Fondamentaux')->firstOrFail();
    expect($formation->categorie)->toBe('Community management');
    expect($formation->description)->toBe('Apprenez à planifier, publier et modérer.');
    expect($formation->programme)->toBe("Semaine 1 — Fondamentaux\nSemaine 2 — Création de contenu");
    expect($formation->duree)->toBe('3 semaines');
    expect($formation->prerequis)->toBe('Aucun — niveau débutant');

    $formation->update(['statut' => 'PUBLIEE']);
    $this->get(route('formations.show', $formation))
        ->assertInertia(fn ($page) => $page->where('formation.programme', "Semaine 1 — Fondamentaux\nSemaine 2 — Création de contenu"));
});

test('filling the content when adding a session to an existing formation updates it, and leaving it empty changes nothing', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create([
        'nom' => 'Montage vidéo',
        'programme' => 'Ancien programme',
        'duree' => '2 semaines',
    ]);

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo',
        'ville' => 'Douala',
        'mode' => 'PRESENTIEL',
        'places' => 10,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    expect($formation->fresh()->programme)->toBe('Ancien programme');

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.sessions.store-rapide'), [
        'formation_nom' => 'Montage vidéo',
        'programme' => 'Nouveau programme',
        'ville' => 'Yaoundé',
        'mode' => 'PRESENTIEL',
        'places' => 10,
        'prix' => 75000,
        'statut' => 'OUVERTE',
    ])->assertRedirect();

    $formation->refresh();
    expect($formation->programme)->toBe('Nouveau programme');
    expect($formation->duree)->toBe('2 semaines');
});

test('the programme of a formation can be edited from its page', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create(['programme' => null]);

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.formations.update', $formation), [
        'nom' => $formation->nom,
        'description' => $formation->description,
        'programme' => "Jour 1 — Bases\nJour 2 — Pratique",
        'statut' => 'PUBLIEE',
    ])->assertRedirect();

    expect($formation->fresh()->programme)->toBe("Jour 1 — Bases\nJour 2 — Pratique");

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.show', $formation))
        ->assertInertia(fn ($page) => $page->where('formation.programme', "Jour 1 — Bases\nJour 2 — Pratique"));
});

test('editing a session shows and updates the formation content shared by all its sessions', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create([
        'description' => 'Description actuelle',
        'programme' => 'Semaine 1',
        'categorie' => 'Marketing',
        'duree' => '2 semaines',
        'prerequis' => 'Aucun',
    ]);
    $session = FormationSession::factory()->for($formation)->create();
    $autre = FormationSession::factory()->for($formation)->create();

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.formations.index'))
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 2)
            ->where('sessions.0.programme', 'Semaine 1')
            ->where('sessions.0.categorie', 'Marketing')
            ->where('sessions.0.duree', '2 semaines')
            ->where('sessions.0.description', 'Description actuelle'));

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.sessions.update', $session), [
        'categorie' => 'Communication',
        'description' => '',
        'programme' => "Semaine 1\nSemaine 2",
        'duree' => '',
        'prerequis' => 'Niveau débutant',
        'ville' => $session->ville,
        'mode' => $session->mode,
        'places' => $session->places,
        'prix' => 75000,
        'statut' => $session->statut,
    ])->assertRedirect();

    $formation->refresh();
    expect($formation->categorie)->toBe('Communication');
    expect($formation->programme)->toBe("Semaine 1\nSemaine 2");
    expect($formation->duree)->toBeNull();
    expect($formation->prerequis)->toBe('Niveau débutant');
    expect($formation->description)->toBe('Description actuelle');
    expect($autre->fresh()->formation_id)->toBe($formation->id);
});
