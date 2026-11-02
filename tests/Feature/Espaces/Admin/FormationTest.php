<?php

use App\Enums\MembreEquipeRole;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\MembreEquipe;
use App\Models\Seance;

test('a promotrice can create a formation then add a session and a seance to it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice->user)->post(route('espace-admin.formations.store'), [
        'nom' => 'Design UI/UX',
        'description' => 'Apprendre les bases du design produit.',
        'categorie' => 'Design',
        'duree' => '4 semaines',
    ])->assertRedirect();

    $formation = Formation::where('nom', 'Design UI/UX')->firstOrFail();
    expect($formation->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.formations.show', $formation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-formation-show')
            ->where('formation.nom', 'Design UI/UX')
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

test('a promotrice can delete a seance', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $seance = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2027-01-10']);

    $this->actingAs($promotrice->user)
        ->delete(route('espace-admin.seances.destroy', $seance))
        ->assertRedirect();

    $this->assertDatabaseMissing('seances', ['id' => $seance->id]);
});

test('a role without access to formations cannot reach the pages', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $formation = Formation::factory()->create();

    $this->actingAs($membre->user)
        ->get(route('espace-admin.formations.index'))
        ->assertOk();

    $this->actingAs($membre->user)->post(route('espace-admin.formations.store'), [
        'nom' => 'Test',
        'description' => 'Test',
    ])->assertForbidden();
});
