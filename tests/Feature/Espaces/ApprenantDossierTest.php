<?php

use App\Models\Apprenant;
use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the apprenant dossier page exposes the current profile and cv', function () {
    $apprenant = Apprenant::factory()->create([
        'ville' => 'Douala',
        'telephone' => '+237699000000',
        'date_naissance' => '2000-05-14',
        'cv_path' => 'apprenants-cv/mon-cv.pdf',
        'consentement_cv' => true,
    ]);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.dossier'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/dossier')
        ->where('dossier.ville', 'Douala')
        ->where('dossier.dateNaissance', '2000-05-14')
        ->where('dossier.cvNomFichier', 'mon-cv.pdf')
        ->where('dossier.consentementCv', true));
});

test('an apprenant can update their dossier and upload a new cv', function () {
    Storage::fake('local');

    $apprenant = Apprenant::factory()->create(['ville' => 'Douala']);

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.dossier.update'), [
        'ville' => 'Yaoundé',
        'telephone' => '+237698000000',
        'date_naissance' => '1999-01-01',
        'consentement_cv' => true,
        'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
    ]);

    $response->assertRedirect(route('espace-apprenant.dossier'));

    $apprenant->refresh();
    expect($apprenant->ville)->toBe('Yaoundé');
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
