<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Formule;
use App\Models\Inscription;
use App\Models\Service;

test('a guest asking for a service is sent to the login page with an explanation', function () {
    $service = Service::factory()->create(['nom' => 'Site vitrine']);
    $url = route('espace-client.demandes.create', ['service' => $service->id]);

    $this->get($url)->assertRedirect(route('login'));

    $this->get(route('login'))->assertInertia(fn ($page) => $page
        ->component('auth/login')
        ->where('intention.message', 'Connectez-vous pour demander le service « Site vitrine ».')
        ->where('intention.compteType', 'particulier'));
});

test('a guest choosing a subscription is told which formule and keeps it after login', function () {
    $formule = Formule::factory()->create(['nom' => 'Croissance']);
    $client = Client::factory()->create();

    $this->get(route('espace-client.demandes.create', ['formule' => $formule->id]))
        ->assertRedirect(route('login'));

    $this->get(route('register'))->assertInertia(fn ($page) => $page
        ->where('intention.message', 'Connectez-vous pour choisir la formule « Croissance ».'));

    $this->post(route('login.store'), ['email' => $client->user->email, 'password' => 'password'])
        ->assertRedirect(route('espace-client.demandes.create', ['formule' => $formule->id]));
});

test('a guest enrolling in a session is invited to sign in as an apprenant', function () {
    $session = FormationSession::factory()->for(Formation::factory()->create(['nom' => 'Community Management']))->create();

    $this->get(route('espace-apprenant.inscriptions.create', $session))->assertRedirect(route('login'));

    $this->get(route('register'))->assertInertia(fn ($page) => $page
        ->where('intention.message', 'Connectez-vous pour vous inscrire à la formation « Community Management ».')
        ->where('intention.compteType', 'apprenant'));
});

test('the login page has no intention without a pending action', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('intention', null));
});

test('the demande form preselects the chosen formule', function () {
    $formule = Formule::factory()->create();
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->get(route('espace-client.demandes.create', ['formule' => $formule->id]))
        ->assertInertia(fn ($page) => $page->where('formuleSelectionnee', $formule->id));
});

test('an apprenant enrols in an open session and waits for payment', function () {
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create();

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.inscriptions.create', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('espaces/apprenant/inscription-create')->where('session.ouverte', true));

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session))
        ->assertRedirect(route('espace-apprenant.inscriptions'));

    expect(Inscription::where('apprenant_id', $apprenant->id)->where('formation_session_id', $session->id)->value('statut'))
        ->toBe('EN_ATTENTE_PAIEMENT');
});

test('enrolling twice in the same session creates a single inscription', function () {
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create();

    $this->actingAs($apprenant->user)->post(route('espace-apprenant.inscriptions.store', $session));
    $this->actingAs($apprenant->user)->post(route('espace-apprenant.inscriptions.store', $session))
        ->assertRedirect(route('espace-apprenant.inscriptions'));

    expect(Inscription::where('apprenant_id', $apprenant->id)->count())->toBe(1);
});

test('enrolment is refused on a closed or full session', function () {
    $apprenant = Apprenant::factory()->create();
    $fermee = FormationSession::factory()->create(['statut' => 'TERMINEE']);
    $complete = FormationSession::factory()->create(['places' => 1]);
    Inscription::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => $complete->formation_id,
        'formation_session_id' => $complete->id,
        'statut' => 'VALIDEE',
    ]);

    $this->actingAs($apprenant->user)->post(route('espace-apprenant.inscriptions.store', $fermee))->assertStatus(409);
    $this->actingAs($apprenant->user)->post(route('espace-apprenant.inscriptions.store', $complete))->assertStatus(409);

    expect(Inscription::where('apprenant_id', $apprenant->id)->count())->toBe(0);
});

test('a client account cannot enrol in a formation', function () {
    $client = Client::factory()->create();
    $session = FormationSession::factory()->create();

    $this->actingAs($client->user)
        ->post(route('espace-apprenant.inscriptions.store', $session))
        ->assertForbidden();
});
