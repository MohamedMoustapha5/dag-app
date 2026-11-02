<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\EntreprisePartenaire;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;

test('a promotrice can validate a pending entreprise partner', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $entreprise = EntreprisePartenaire::factory()->create(['statut' => 'EN_ATTENTE']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.entreprises.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-entreprises')
            ->has('entreprises', 1)
            ->where('entreprises.0.statut', 'EN_ATTENTE')
            ->where('peutValider', true));

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.entreprises.update', $entreprise), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($entreprise->fresh()->statut)->toBe('ACTIF');
});

test('a promotrice can publish a draft offre and the entreprise client can then see it live', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'PUBLIEE'])
        ->assertRedirect();

    expect($offre->fresh()->statut)->toBe('PUBLIEE');
});

test('a promotrice can close a published offre', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'CLOTUREE'])
        ->assertRedirect();

    expect($offre->fresh()->statut)->toBe('CLOTUREE');
});

test('a role with gestion but not validation can see but not publish or validate', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $entreprise = EntreprisePartenaire::factory()->create(['statut' => 'EN_ATTENTE']);
    $offre = OffreEmploi::factory()->create(['statut' => 'BROUILLON']);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.entreprises.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('peutValider', false)
            ->where('peutModifier', true));

    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.entreprises.update', $entreprise), ['statut' => 'ACTIF'])
        ->assertForbidden();

    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.offres.update', $offre), ['statut' => 'PUBLIEE'])
        ->assertForbidden();

    expect($entreprise->fresh()->statut)->toBe('EN_ATTENTE');
    expect($offre->fresh()->statut)->toBe('BROUILLON');
});

test('a role without any access to entreprises offres cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.entreprises.index'))
        ->assertForbidden();
});

test('an admin can apply to a published offre on behalf of an apprenant, including one already trained', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.entreprises.index'))
        ->assertInertia(fn ($page) => $page
            ->where('peutPostuler', true)
            ->has('apprenants', 1)
            ->where('offres.0.postulable', true));

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertRedirect();

    $candidature = Candidature::firstOrFail();
    expect($candidature->apprenant_id)->toBe($apprenant->id);
    expect($candidature->statut)->toBe('DEPOSEE');
    expect($candidature->deposee_par_id)->toBe($promotrice->id);
});

test('an admin cannot apply twice for the same apprenant on the same offre', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);
    Candidature::factory()->create(['apprenant_id' => $apprenant->id, 'offre_id' => $offre->id]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertSessionHasErrors('apprenant_id');

    expect(Candidature::count())->toBe(1);
});

test('an admin cannot apply for an apprenant who did not consent to sharing their dossier', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create(['consentement_cv' => false]);
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertSessionHasErrors('apprenant_id');

    expect(Candidature::count())->toBe(0);
});

test('an admin cannot apply to an offre that is not published', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'CLOTUREE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertStatus(409);
});

test('a lecture-only role cannot apply to offres', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $apprenant = Apprenant::factory()->create();
    $offre = OffreEmploi::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.entreprises.index'))
        ->assertInertia(fn ($page) => $page->where('peutPostuler', false)->where('apprenants', []));

    $this->actingAs($juriste, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertForbidden();
});
