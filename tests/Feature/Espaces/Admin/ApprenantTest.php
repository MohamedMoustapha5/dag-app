<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\MembreEquipe;
use Illuminate\Support\Facades\Storage;

test('a promotrice can list and view an apprenant dossier', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create(['ville' => 'Douala']);

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.apprenants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-apprenants')
            ->has('apprenants', 1)
            ->where('apprenants.0.id', $apprenant->id));

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.apprenants.show', $apprenant))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-apprenant-show')
            ->where('apprenant.ville', 'Douala'));
});

test('a promotrice can download an apprenant cv regardless of their sharing consent', function () {
    Storage::fake('local');

    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create([
        'cv_path' => 'apprenants-cv/cv-test.pdf',
        'consentement_cv' => false,
    ]);
    Storage::disk('local')->put($apprenant->cv_path, 'contenu-du-cv');

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.apprenants.cv', $apprenant))
        ->assertOk();
});

test('downloading a cv that was never imported returns a 404', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create(['cv_path' => null]);

    $this->actingAs($promotrice->user)
        ->get(route('espace-admin.apprenants.cv', $apprenant))
        ->assertNotFound();
});

test('a role without access to dossiers apprenants cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);

    $this->actingAs($membre->user)
        ->get(route('espace-admin.apprenants.index'))
        ->assertForbidden();
});
