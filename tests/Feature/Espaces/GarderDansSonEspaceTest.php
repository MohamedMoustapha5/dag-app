<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Formation;
use App\Models\MembreEquipe;

test('a signed-in client opening a public page lands on its counterpart in the client espace', function (string $page, string $destination) {
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->get(route($page))
        ->assertRedirect(route($destination));
})->with([
    'home' => ['home', 'espace-client.dashboard'],
    'catalogue' => ['catalogue', 'espace-client.services'],
    'grille tarifaire' => ['grille-tarifaire', 'espace-client.services'],
    'abonnement' => ['abonnement', 'espace-client.abonnements'],
    'contact' => ['contact', 'espace-client.support.index'],
    'formations' => ['formations', 'espace-client.dashboard'],
    'à propos' => ['a-propos', 'espace-client.dashboard'],
    'offres d\'emploi' => ['offres-emploi', 'espace-client.dashboard'],
]);

test('a signed-in apprenant opening a public page lands on its counterpart in the apprenant espace', function (string $page, string $destination) {
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($apprenant->user)
        ->get(route($page))
        ->assertRedirect(route($destination));
})->with([
    'home' => ['home', 'espace-apprenant.dashboard'],
    'formations' => ['formations', 'espace-apprenant.formations'],
    'contact' => ['contact', 'espace-apprenant.support.index'],
    'catalogue' => ['catalogue', 'espace-apprenant.dashboard'],
]);

test('a public formation page sends a signed-in apprenant to the espace catalogue', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);

    $this->actingAs($apprenant->user)
        ->get(route('formations.show', $formation))
        ->assertRedirect(route('espace-apprenant.formations'));
});

test('visitors and team members still browse the public site', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('catalogue'))->assertOk();

    $this->actingAs(MembreEquipe::factory()->create(), 'admin')
        ->get(route('catalogue'))
        ->assertOk();
});

test('on the public site, a visitor gets no espace link and a team member gets the admin one', function () {
    $this->get(route('contact'))->assertInertia(fn ($page) => $page->where('espaceUrl', null));

    $this->actingAs(MembreEquipe::factory()->create(), 'admin')
        ->get(route('contact'))
        ->assertInertia(fn ($page) => $page->where('espaceUrl', route('espace-admin.dashboard', absolute: false)));
});

test('the public forms keep working for a signed-in client', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->post(route('contact.store'), [
            'nom' => 'Aïcha',
            'email' => 'aicha@example.com',
            'sujet' => 'Question',
            'message' => 'Bonjour.',
        ])
        ->assertRedirect();
});
