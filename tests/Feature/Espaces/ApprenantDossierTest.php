<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the apprenant dossier page exposes the current profile and cv', function () {
    $apprenant = Apprenant::factory()->create([
        'ville' => 'Douala',
        'telephone' => '+237699000000',
        'cv_path' => 'apprenants-cv/mon-cv.pdf',
        'consentement_cv' => true,
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.dossier'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/dossier')
        ->where('dossier.ville', 'Douala')
        ->where('dossier.telephone', '+237699000000')
        ->where('dossier.cvNomFichier', 'mon-cv.pdf')
        ->where('dossier.consentementCv', true));
});

test('the apprenant dossier page exposes the active formation and presences', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['nom' => 'Community Management']);
    $session = FormationSession::factory()->for($formation)->create();

    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);

    $seance = Seance::create(['formation_session_id' => $session->id, 'titre' => 'Introduction', 'date' => '2026-11-01']);
    Presence::create(['seance_id' => $seance->id, 'inscription_id' => $inscription->id, 'present' => true]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.dossier'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/dossier')
        ->where('formationSuivie.nom', 'Community Management')
        ->where('formationSuivie.seancesCompletees', 1)
        ->where('formationSuivie.seancesTotal', 1)
        ->missing('candidatures')
        ->has('presences', 1)
        ->where('presences.0.present', true));
});

test('an apprenant can replace their cv and toggle sharing consent', function () {
    Storage::fake('local');

    $apprenant = Apprenant::factory()->create(['consentement_cv' => false]);

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.dossier.update'), [
        'consentement_cv' => true,
        'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
    ]);

    $response->assertRedirect(route('espace-apprenant.dossier'));

    $apprenant->refresh();
    expect($apprenant->consentement_cv)->toBeTrue();
    Storage::disk('local')->assertExists($apprenant->cv_path);
});

test('the dossier update rejects a cv that is not a pdf or word document', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.dossier.update'), [
        'cv' => UploadedFile::fake()->create('cv.exe', 200, 'application/octet-stream'),
    ]);

    $response->assertSessionHasErrors('cv');
});

test('a client cannot reach the apprenant dossier page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.dossier'))->assertForbidden();
});
