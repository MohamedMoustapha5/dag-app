<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\MembreEquipe;
use App\Models\TicketSupport;
use App\Notifications\AlerteEquipeNotification;
use Illuminate\Support\Facades\Notification;

test('a client opens a support request from their espace and the team is alerted', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha N.']);

    $this->actingAs($client->user)
        ->get(route('espace-client.support.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/support-index')
            ->where('client.nom', 'Aïcha N.')
            ->where('expediteur.nom', 'Aïcha N.')
            ->where('expediteur.email', $client->user->email)
            ->has('tickets', 0));

    $this->actingAs($client->user)
        ->post(route('espace-client.support.store'), [
            'categorie' => 'FACTURATION',
            'sujet' => 'Facture en double',
            'message' => 'Ma facture apparaît deux fois.',
        ])
        ->assertRedirect();

    $ticket = TicketSupport::sole();
    expect($ticket->user_id)->toBe($client->user_id);
    expect($ticket->origine)->toBe('ESPACE');
    expect($ticket->nom)->toBe('Aïcha N.');
    expect($ticket->statut)->toBe('OUVERT');
    expect($ticket->messages()->sole()->contenu)->toBe('Ma facture apparaît deux fois.');
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);

    $this->actingAs($client->user)
        ->get(route('espace-client.support.show', $ticket))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/support-show')
            ->where('ticket.sujet', 'Facture en double')
            ->has('messages', 1)
            ->where('messages.0.deDag', false));
});

test('an apprenant reaches support inside the apprenant espace', function () {
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.support.store'), [
            'categorie' => 'FORMATION',
            'sujet' => 'Horaires de la session',
            'message' => 'À quelle heure commence la séance ?',
        ])
        ->assertRedirect(route('espace-apprenant.support.show', TicketSupport::sole()));

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.support.show', TicketSupport::sole()))
        ->assertInertia(fn ($page) => $page->component('espaces/apprenant/support-show'));
});

test('opening a support request requires a known category, a subject and a message', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->post(route('espace-client.support.store'), ['categorie' => 'INCONNUE', 'sujet' => '', 'message' => ''])
        ->assertSessionHasErrors(['categorie', 'sujet', 'message']);

    expect(TicketSupport::count())->toBe(0);
});

test('nobody can read or write in the support thread of another account', function () {
    $proprietaire = Client::factory()->create();
    $autre = Client::factory()->create();
    $ticket = TicketSupport::factory()->create(['user_id' => $proprietaire->user_id]);

    $this->actingAs($autre->user)->get(route('espace-client.support.show', $ticket))->assertNotFound();
    $this->actingAs($autre->user)
        ->post(route('espace-client.support.repondre', $ticket), ['message' => 'Intrusion'])
        ->assertNotFound();
    $this->actingAs($autre->user)->patch(route('espace-client.support.clore', $ticket))->assertNotFound();

    expect($ticket->messages()->count())->toBe(0);
});

test('a client only lists their own support requests', function () {
    $client = Client::factory()->create();
    TicketSupport::factory()->create(['user_id' => $client->user_id]);
    TicketSupport::factory()->create();

    $this->actingAs($client->user)
        ->get(route('espace-client.support.index'))
        ->assertInertia(fn ($page) => $page->has('tickets', 1));
});

test('replying to an answered or closed request sends it back to the team', function (string $statut) {
    Notification::fake();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $client = Client::factory()->create();
    $ticket = TicketSupport::factory()->create(['user_id' => $client->user_id, 'statut' => $statut]);

    $this->actingAs($client->user)
        ->post(route('espace-client.support.repondre', $ticket), ['message' => 'Toujours bloqué.'])
        ->assertRedirect();

    expect($ticket->fresh()->statut)->toBe('OUVERT');
    Notification::assertSentTo($marketing, AlerteEquipeNotification::class);
})->with(['REPONDU', 'CLOS']);

test('the requester can close their own request', function () {
    $client = Client::factory()->create();
    $ticket = TicketSupport::factory()->create(['user_id' => $client->user_id, 'statut' => 'REPONDU']);

    $this->actingAs($client->user)->patch(route('espace-client.support.clore', $ticket))->assertRedirect();

    expect($ticket->fresh()->statut)->toBe('CLOS');
});

test('closing an account removes its support threads', function () {
    $client = Client::factory()->create();
    TicketSupport::factory()->create(['user_id' => $client->user_id]);

    $this->actingAs($client->user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect(TicketSupport::count())->toBe(0);
});
