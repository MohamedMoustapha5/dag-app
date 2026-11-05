<?php

use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\CandidatureSpontanee;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function dossierRecu(array $attributes = []): CandidatureSpontanee
{
    return CandidatureSpontanee::create([
        'nom' => 'Paul Mbarga',
        'email' => 'paul@example.com',
        'telephone' => '+237 600000000',
        'cv_path' => 'candidatures-spontanees/cv-paul.pdf',
        'statut' => 'NOUVELLE',
        ...$attributes,
    ]);
}

test('the dossiers apprenants page lists the dossiers received from external candidates', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    dossierRecu(['message' => 'Je cherche un stage.']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.apprenants.index'))
        ->assertInertia(fn ($page) => $page
            ->has('dossiersRecus', 1)
            ->where('dossiersRecus.0.nom', 'Paul Mbarga')
            ->where('dossiersRecus.0.statut', 'NOUVELLE')
            ->where('peutPostuler', true));
});

test('an admin can download the cv of a received dossier', function () {
    Storage::fake('local');
    Storage::disk('local')->put('candidatures-spontanees/cv-paul.pdf', 'pdf');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $dossier = dossierRecu();

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.candidats-externes.cv', $dossier))
        ->assertOk()
        ->assertDownload('CV-Paul Mbarga.pdf');
});

test('applying for an external candidate turns the dossier into an apprenant dossier and files the candidature', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $dossier = dossierRecu();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['candidature_spontanee_id' => $dossier->id])
        ->assertRedirect();

    $apprenant = Apprenant::firstOrFail();
    expect($apprenant->user->email)->toBe('paul@example.com');
    expect($apprenant->cv_path)->toBe('candidatures-spontanees/cv-paul.pdf');
    expect($apprenant->consentement_cv)->toBeTrue();
    expect($dossier->fresh()->statut)->toBe('TRAITEE');
    expect($dossier->fresh()->apprenant_id)->toBe($apprenant->id);

    $candidature = Candidature::firstOrFail();
    expect($candidature->apprenant_id)->toBe($apprenant->id);
    expect($candidature->deposee_par_id)->toBe($promotrice->id);

    $autreOffre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);
    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $autreOffre), ['candidature_spontanee_id' => $dossier->id])
        ->assertRedirect();

    expect(Apprenant::count())->toBe(1);
    expect(Candidature::count())->toBe(2);
});

test('applying for an external candidate reuses an existing apprenant account with the same e-mail', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $existant = Apprenant::factory()->create();
    $dossier = dossierRecu(['email' => $existant->user->email]);
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['candidature_spontanee_id' => $dossier->id])
        ->assertRedirect();

    expect(Apprenant::count())->toBe(1);
    expect(Candidature::firstOrFail()->apprenant_id)->toBe($existant->id);
    expect($dossier->fresh()->apprenant_id)->toBeNull();
});

test('a role that cannot create dossiers cannot apply for an external candidate', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $dossier = dossierRecu();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($juriste, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['candidature_spontanee_id' => $dossier->id])
        ->assertForbidden();

    expect(User::count())->toBe(0);
});

test('deleting a received dossier also removes the dossier apprenant created from it', function () {
    Storage::fake('local');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $dossier = dossierRecu();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['candidature_spontanee_id' => $dossier->id]);

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.candidats-externes.destroy', $dossier->fresh()))
        ->assertRedirect();

    expect(CandidatureSpontanee::count())->toBe(0);
    expect(Apprenant::count())->toBe(0);
    expect(User::where('email', 'paul@example.com')->exists())->toBeFalse();
});

test('an external dossier is not listed among the accounts apprenants created themselves', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $propre = Apprenant::factory()->create();
    $dossier = dossierRecu();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['candidature_spontanee_id' => $dossier->id]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.clients.index'))
        ->assertInertia(fn ($page) => $page
            ->has('apprenants', 1)
            ->where('apprenants.0.id', $propre->id));
});

test('closing an apprenant account that holds factures anonymises it and keeps the factures attached', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create(['telephone' => '690000000', 'ville' => 'Douala']);
    $facture = Facture::factory()->create(['type' => 'FORMATION', 'client_id' => null, 'apprenant_id' => $apprenant->id]);
    $email = $apprenant->user->email;

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.apprenants.destroy', $apprenant))
        ->assertRedirect();

    $apprenant->refresh();
    expect($facture->fresh()->apprenant_id)->toBe($apprenant->id);
    expect($apprenant->user->name)->toBe('Compte supprimé');
    expect($apprenant->user->email)->not->toBe($email);
    expect($apprenant->telephone)->toBeNull();
    expect($apprenant->ville)->toBeNull();
    expect(User::where('email', $email)->exists())->toBeFalse();
});

test('closing an apprenant account without any legal record deletes it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $userId = $apprenant->user_id;

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.apprenants.destroy', $apprenant))
        ->assertRedirect();

    expect(Apprenant::count())->toBe(0);
    expect(User::find($userId))->toBeNull();
});

test('a lecture-only role cannot delete an apprenant account', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($juriste, 'admin')
        ->delete(route('espace-admin.apprenants.destroy', $apprenant))
        ->assertForbidden();

    expect(Apprenant::count())->toBe(1);
});
