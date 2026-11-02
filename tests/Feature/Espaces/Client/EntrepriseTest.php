<?php

use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use Illuminate\Support\Facades\Storage;

test('an entreprise client without a validated partner link cannot reach the recruitment routes', function () {
    $client = Client::factory()->create(['type' => 'ENTREPRISE']);

    $this->actingAs($client->user)->get(route('espace-client.offres.index'))->assertForbidden();
    $this->actingAs($client->user)->get(route('espace-client.candidatures.index'))->assertForbidden();
});

test('an entreprise client with an inactive partner link cannot reach the recruitment routes', function () {
    $client = Client::factory()->create(['type' => 'ENTREPRISE']);
    EntreprisePartenaire::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ATTENTE']);

    $this->actingAs($client->user)->get(route('espace-client.offres.index'))->assertForbidden();
});

test('a validated entreprise partner sees the recruitment dashboard, others see the generic one', function () {
    $partenaire = clientPartenaireValide();
    $particulier = Client::factory()->create(['type' => 'PARTICULIER']);

    $this->actingAs($partenaire->user)->get(route('espace-client.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('espaces/client/entreprise-dashboard'));

    $this->actingAs($particulier->user)->get(route('espace-client.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('espaces/client-dashboard'));
});

test('a validated entreprise partner can send an offre to DAG and see it listed', function () {
    $client = clientPartenaireValide();

    $this->actingAs($client->user)->post(route('espace-client.offres.store'), [
        'titre' => 'Chargé(e) de community management',
        'description' => 'Gestion des réseaux sociaux au quotidien.',
        'type_contrat' => 'STAGE',
    ])->assertRedirect();

    $offre = OffreEmploi::where('entreprise_id', $client->entreprisePartenaire->id)->first();

    expect($offre)->not->toBeNull();
    // L'offre est envoyée à DAG pour validation — seul un membre de
    // l'équipe DAG peut la faire passer à PUBLIEE.
    expect($offre->statut)->toBe('BROUILLON');

    $this->actingAs($client->user)->get(route('espace-client.offres.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/offres-index')
            ->has('offres', 1)
            ->where('offres.0.id', $offre->id));
});

test('a validated entreprise partner can select a candidature on its own offre', function () {
    $client = clientPartenaireValide();
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $client->entreprisePartenaire->id]);
    $candidature = Candidature::factory()->create(['offre_id' => $offre->id, 'statut' => 'EN_ETUDE']);

    $this->actingAs($client->user)
        ->patch(route('espace-client.candidatures.update', $candidature), ['statut' => 'RETENUE'])
        ->assertRedirect();

    expect($candidature->fresh()->statut)->toBe('RETENUE');
});

test('an entreprise partner cannot act on another partner candidature', function () {
    $mine = clientPartenaireValide();
    $autre = clientPartenaireValide();

    $offreAutre = OffreEmploi::factory()->create(['entreprise_id' => $autre->entreprisePartenaire->id]);
    $candidature = Candidature::factory()->create(['offre_id' => $offreAutre->id, 'statut' => 'EN_ETUDE']);

    $this->actingAs($mine->user)
        ->patch(route('espace-client.candidatures.update', $candidature), ['statut' => 'RETENUE'])
        ->assertForbidden();

    $this->actingAs($mine->user)
        ->get(route('espace-client.offres.show', $offreAutre))
        ->assertForbidden();

    $this->actingAs($mine->user)
        ->get(route('espace-client.candidatures.show', $candidature))
        ->assertForbidden();

    $this->actingAs($mine->user)
        ->get(route('espace-client.candidatures.cv', $candidature))
        ->assertForbidden();

    expect($candidature->fresh()->statut)->toBe('EN_ETUDE');
});

test('a validated entreprise partner can view a candidate dossier before deciding', function () {
    $client = clientPartenaireValide();
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $client->entreprisePartenaire->id]);
    $apprenant = Apprenant::factory()->create(['consentement_cv' => true, 'cv_path' => 'apprenants-cv/cv-test.pdf']);
    $candidature = Candidature::factory()->create([
        'offre_id' => $offre->id,
        'apprenant_id' => $apprenant->id,
        'statut' => 'EN_ETUDE',
    ]);

    $this->actingAs($client->user)
        ->get(route('espace-client.candidatures.show', $candidature))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/candidatures-show')
            ->where('candidature.id', $candidature->id)
            ->where('apprenant.nom', $apprenant->user->name)
            ->where('cv.disponible', true));
});

test('an entreprise cannot download a candidate cv without the candidate consent', function () {
    Storage::fake('local');

    $client = clientPartenaireValide();
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $client->entreprisePartenaire->id]);
    $apprenant = Apprenant::factory()->create(['consentement_cv' => false, 'cv_path' => 'apprenants-cv/cv-test.pdf']);
    Storage::disk('local')->put($apprenant->cv_path, 'contenu-du-cv');
    $candidature = Candidature::factory()->create(['offre_id' => $offre->id, 'apprenant_id' => $apprenant->id]);

    $this->actingAs($client->user)
        ->get(route('espace-client.candidatures.cv', $candidature))
        ->assertNotFound();
});

test('an entreprise can download a candidate cv once the candidate has consented', function () {
    Storage::fake('local');

    $client = clientPartenaireValide();
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $client->entreprisePartenaire->id]);
    $apprenant = Apprenant::factory()->create(['consentement_cv' => true, 'cv_path' => 'apprenants-cv/cv-test.pdf']);
    Storage::disk('local')->put($apprenant->cv_path, 'contenu-du-cv');
    $candidature = Candidature::factory()->create(['offre_id' => $offre->id, 'apprenant_id' => $apprenant->id]);

    $this->actingAs($client->user)
        ->get(route('espace-client.candidatures.cv', $candidature))
        ->assertOk();
});

test('factures billed to the linked entreprise, not the client, still show in the espace', function () {
    $client = clientPartenaireValide();

    $facture = Facture::factory()->create([
        'client_id' => null,
        'entreprise_id' => $client->entreprisePartenaire->id,
        'statut' => 'PAYEE',
    ]);

    $this->actingAs($client->user)->get(route('espace-client.factures'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/factures-index')
            ->has('factures', 1)
            ->where('factures.0.id', $facture->id));
});

test('a convention de stage sent to the entreprise counts toward contrats à signer', function () {
    $client = clientPartenaireValide();

    Contrat::factory()->create([
        'client_id' => null,
        'entreprise_id' => $client->entreprisePartenaire->id,
        'type' => 'CONVENTION_STAGE',
        'statut' => 'ENVOYE',
    ]);

    $this->actingAs($client->user)->get(route('espace-client.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('candidaturesASigner', 1));
});

test('an entreprise partner sees which candidatures DAG submitted and can reject them', function () {
    $client = clientPartenaireValide();
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $client->entreprisePartenaire->id]);
    $membre = MembreEquipe::factory()->promotrice()->create();
    $candidature = Candidature::factory()->create([
        'offre_id' => $offre->id,
        'statut' => 'DEPOSEE',
        'deposee_par_id' => $membre->id,
    ]);

    $this->actingAs($client->user)
        ->get(route('espace-client.candidatures.index'))
        ->assertInertia(fn ($page) => $page->where('candidatures.0.deposeeParDag', true));

    $this->actingAs($client->user)
        ->get(route('espace-client.candidatures.show', $candidature))
        ->assertInertia(fn ($page) => $page->where('candidature.deposeeParDag', true));

    $this->actingAs($client->user)
        ->patch(route('espace-client.candidatures.update', $candidature), ['statut' => 'REFUSEE'])
        ->assertRedirect();

    expect($candidature->fresh()->statut)->toBe('REFUSEE');
});
