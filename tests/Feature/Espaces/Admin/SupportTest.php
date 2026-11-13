<?php

use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Models\Client;
use App\Models\MembreEquipe;
use App\Models\TicketSupport;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\SupportReponseNotification;
use App\Support\DroitsMatrix;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

test('the team sees the support inbox, requests to handle first', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $clos = TicketSupport::factory()->create(['statut' => 'CLOS']);
    $ouvert = TicketSupport::factory()->public()->create(['statut' => 'OUVERT']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.support.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin-support')
            ->has('tickets', 2)
            ->where('tickets.0.id', $ouvert->id)
            ->where('tickets.0.provenance', 'Visiteur (formulaire de contact)')
            ->where('tickets.1.id', $clos->id));
});

test('answering a client request notifies them and waits for their reply', function () {
    Notification::fake();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $client = Client::factory()->create();
    $ticket = TicketSupport::factory()->create(['user_id' => $client->user_id]);
    $ticket->ajouterMessage('Ma facture apparaît deux fois.');

    $this->actingAs($marketing, 'admin')
        ->post(route('espace-admin.support.repondre', $ticket), ['message' => 'Nous corrigeons cela.'])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->statut)->toBe('REPONDU');
    expect($ticket->messages()->where('auteur', 'EQUIPE')->sole()->membre_equipe_id)->toBe($marketing->id);
    Notification::assertSentTo($client->user, SupportReponseNotification::class);
});

test('a visitor of the contact form gets the answer by e-mail', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $ticket = TicketSupport::factory()->public()->create(['email' => 'visiteur@example.com']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.support.repondre', $ticket), ['message' => 'Bonjour, voici notre réponse.'])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        SupportReponseNotification::class,
        fn ($notification, array $canaux, AnonymousNotifiable $destinataire) => $destinataire->routes['mail'] === 'visiteur@example.com'
            && in_array('mail', $canaux, true),
    );
});

test('a read-only role can read the inbox but cannot answer or close', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $ticket = TicketSupport::factory()->create();

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.support.show', $ticket))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('peutRepondre', false));

    $this->actingAs($juriste, 'admin')
        ->post(route('espace-admin.support.repondre', $ticket), ['message' => 'Réponse'])
        ->assertForbidden();
    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.support.update', $ticket), ['statut' => 'CLOS'])
        ->assertForbidden();

    expect($ticket->fresh()->statut)->toBe('OUVERT');
});

test('a role without access to client requests cannot open the inbox', function () {
    $contenu = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($contenu, 'admin')
        ->get(route('espace-admin.support.index'))
        ->assertForbidden();
});

test('the team can close then reopen a request', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $ticket = TicketSupport::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.support.update', $ticket), ['statut' => 'CLOS'])
        ->assertRedirect();
    expect($ticket->fresh()->statut)->toBe('CLOS');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.support.update', $ticket), ['statut' => 'OUVERT'])
        ->assertRedirect();
    expect($ticket->fresh()->statut)->toBe('OUVERT');
});

test('support is its own row of the access matrix, set apart from client requests', function () {
    // A role kept on client requests but taken off support, and the reverse.
    DroitsMatrix::definir(MembreEquipeRole::CommunicationMarketing, Donnee::Support, NiveauDroit::Aucun);
    DroitsMatrix::definir(MembreEquipeRole::CreationContenu, Donnee::Support, NiveauDroit::Gestion);
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $contenu = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);
    $ticket = TicketSupport::factory()->create(['statut' => 'OUVERT']);

    $this->actingAs($marketing, 'admin')->get(route('espace-admin.support.index'))->assertForbidden();
    $this->actingAs($marketing, 'admin')->get(route('espace-admin.demandes.index'))->assertOk();

    $this->actingAs($contenu, 'admin')->get(route('espace-admin.demandes.index'))->assertForbidden();
    $this->actingAs($contenu, 'admin')
        ->post(route('espace-admin.support.repondre', $ticket), ['message' => 'Nous revenons vers vous demain.'])
        ->assertRedirect();
    expect($ticket->messages()->where('contenu', 'Nous revenons vers vous demain.')->exists())->toBeTrue();
});

test('a message from the contact form alerts the roles that handle support', function () {
    Notification::fake();
    DroitsMatrix::definir(MembreEquipeRole::CreationContenu, Donnee::Support, NiveauDroit::Gestion);
    DroitsMatrix::definir(MembreEquipeRole::CommunicationMarketing, Donnee::Support, NiveauDroit::Aucun);
    $contenu = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);

    $this->post(route('contact.store'), [
        'nom' => 'Karim Benali',
        'email' => 'karim@example.com',
        'sujet' => 'Question',
        'message' => 'Bonjour, une question sur vos offres.',
    ])->assertRedirect();

    Notification::assertSentTo($contenu, AlerteEquipeNotification::class);
    Notification::assertNotSentTo($marketing, AlerteEquipeNotification::class);
});

test('the team rights page lists support as a domain the directrice can set', function () {
    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->get(route('espace-admin.equipe.index'))
        ->assertInertia(fn ($page) => $page->where('domaines', fn ($domaines) => collect($domaines)->contains(
            fn ($domaine) => $domaine['value'] === 'SUPPORT' && $domaine['label'] === 'Support',
        )));

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->put(route('espace-admin.equipe.droits.update'), ['role' => 'JURISTE', 'donnee' => 'SUPPORT', 'niveau' => 'GESTION'])
        ->assertRedirect();
    expect(DroitsMatrix::niveau(MembreEquipeRole::Juriste, Donnee::Support))->toBe(NiveauDroit::Gestion);
});

test('on the day support became its own row, every role keeps exactly the access it had', function () {
    foreach (MembreEquipeRole::cases() as $role) {
        expect(DroitsMatrix::defaut($role, Donnee::Support))->toBe(DroitsMatrix::defaut($role, Donnee::DemandesClients));
    }

    // A level the directrice had customised on client requests is carried over.
    DB::table('droits_roles')->where('donnee', 'SUPPORT')->delete();
    DroitsMatrix::definir(MembreEquipeRole::CreationContenu, Donnee::DemandesClients, NiveauDroit::Lecture);

    (require database_path('migrations/2026_11_13_091656_copy_demandes_clients_rights_to_support.php'))->up();

    expect(DroitsMatrix::niveau(MembreEquipeRole::CreationContenu, Donnee::Support))->toBe(NiveauDroit::Lecture);
});
