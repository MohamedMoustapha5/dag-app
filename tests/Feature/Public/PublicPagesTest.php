<?php

use App\Models\AuditRequest;
use App\Models\ContactMessage;
use App\Models\EntreprisePartenaire;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Formule;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use App\Models\Service;

test('the home page renders with published catalogue stats, the active team and published formules', function () {
    Service::factory()->create(['statut' => 'PUBLIE']);
    Service::factory()->create(['statut' => 'BROUILLON']);
    MembreEquipe::factory()->juriste()->create();
    MembreEquipe::factory()->inactif()->create();
    Formule::factory()->create(['statut' => 'PUBLIEE']);
    Formule::factory()->create(['statut' => 'BROUILLON']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/home')
        ->where('stats.services', 1)
        ->has('equipe', 1)
        ->has('formules', 1));
});

test('the catalogue page only lists published services', function () {
    $published = Service::factory()->create(['statut' => 'PUBLIE', 'nom' => 'Audit SEO']);
    Service::factory()->create(['statut' => 'BROUILLON', 'nom' => 'Service caché']);

    $response = $this->get(route('catalogue'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/catalogue')
        ->has('services', 1)
        ->where('services.0.id', $published->id));
});

test('the formations page only lists sessions that are open or full', function () {
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);
    $openSession = FormationSession::factory()->for($formation)->create(['statut' => 'OUVERTE']);
    FormationSession::factory()->for($formation)->create(['statut' => 'BROUILLON']);

    $response = $this->get(route('formations'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/formations')
        ->has('sessions', 1)
        ->where('sessions.0.sessionId', $openSession->id));
});

test('a formation detail page shows its open sessions', function () {
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);
    FormationSession::factory()->for($formation)->create(['statut' => 'OUVERTE']);

    $response = $this->get(route('formations.show', $formation));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/formation-detail')
        ->where('formation.id', $formation->id)
        ->has('sessions', 1));
});

test('the abonnement page lists published formules with popular first', function () {
    $standard = Formule::factory()->create(['statut' => 'PUBLIEE', 'populaire' => false, 'nom' => 'Standard']);
    $popular = Formule::factory()->create(['statut' => 'PUBLIEE', 'populaire' => true, 'nom' => 'Premium']);
    Formule::factory()->create(['statut' => 'BROUILLON', 'nom' => 'Brouillon']);

    $response = $this->get(route('abonnement'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/abonnement')
        ->has('formules', 2)
        ->where('formules.0.id', $popular->id)
        ->where('formules.1.id', $standard->id));
});

test('the contact page renders', function () {
    $this->get(route('contact'))->assertOk()->assertInertia(fn ($page) => $page->component('public/contact'));
});

test('submitting the contact form creates a contact message', function () {
    $response = $this->post(route('contact.store'), [
        'nom' => 'Karim Benali',
        'email' => 'karim@example.com',
        'sujet' => 'Demande de devis',
        'message' => 'Bonjour, je souhaite un devis pour un site vitrine.',
    ]);

    $response->assertRedirect();
    expect(ContactMessage::where('email', 'karim@example.com')->exists())->toBeTrue();
});

test('the contact form rejects invalid input', function () {
    $response = $this->post(route('contact.store'), [
        'nom' => '',
        'email' => 'not-an-email',
        'sujet' => '',
        'message' => '',
    ]);

    $response->assertSessionHasErrors(['nom', 'email', 'sujet', 'message']);
    expect(ContactMessage::count())->toBe(0);
});

test('submitting the free audit form creates an audit request', function () {
    $response = $this->post(route('audit-requests.store'), [
        'page_url' => 'https://exemple-client.com',
        'email' => 'lead@example.com',
    ]);

    $response->assertRedirect();
    expect(AuditRequest::where('email', 'lead@example.com')->exists())->toBeTrue();
});

test('the free audit form rejects invalid input', function () {
    $response = $this->post(route('audit-requests.store'), [
        'page_url' => '',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors(['page_url', 'email']);
    expect(AuditRequest::count())->toBe(0);
});

test('the offres emploi page only lists published, non-expired offers', function () {
    $entreprise = EntreprisePartenaire::factory()->create();
    $published = OffreEmploi::factory()->for($entreprise, 'entreprise')->create(['statut' => 'PUBLIEE', 'titre' => 'Community Manager']);
    OffreEmploi::factory()->for($entreprise, 'entreprise')->create(['statut' => 'CLOTUREE', 'titre' => 'Offre clôturée']);
    OffreEmploi::factory()->for($entreprise, 'entreprise')->create(['statut' => 'PUBLIEE', 'date_limite' => now()->subDay(), 'titre' => 'Offre expirée']);

    $response = $this->get(route('offres-emploi'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/offres-emploi')
        ->has('offres', 1)
        ->where('offres.0.id', $published->id));
});
