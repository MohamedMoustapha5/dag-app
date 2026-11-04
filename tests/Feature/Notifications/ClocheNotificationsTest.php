<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Demande;
use App\Models\MembreEquipe;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\DevisRecuNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('a notification is stored for the bell even when the user switched the messages off', function () {
    $client = Client::factory()->create();
    $client->user->update(['notif_devis' => false]);
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);

    Notification::sendNow($client->user->fresh(), new DevisRecuNotification($demande));

    $notification = $client->user->notifications()->firstOrFail();
    expect($notification->data['titre'])->toContain($demande->reference)
        ->and($notification->data['url'])->toBe("/espace-client/demandes/{$demande->id}")
        ->and($notification->read_at)->toBeNull();
});

test('the client space shares the unread count and the latest notifications', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);
    Notification::sendNow($client->user, new DevisRecuNotification($demande));

    $this->actingAs($client->user)
        ->get(route('espace-client.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('cloche.nonLues', 1)
            ->has('cloche.recentes', 1)
            ->where('cloche.recentes.0.lue', false));
});

test('opening a notification marks it as read and goes to its page', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);
    Notification::sendNow($client->user, new DevisRecuNotification($demande));
    $notification = $client->user->notifications()->firstOrFail();

    $this->actingAs($client->user)
        ->post(route('notifications.lire', $notification->id))
        ->assertRedirect("/espace-client/demandes/{$demande->id}");

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all as read clears the unread count', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);
    Notification::sendNow($client->user, new DevisRecuNotification($demande));
    Notification::sendNow($client->user, new DevisRecuNotification($demande));

    $this->actingAs($client->user)->post(route('notifications.toutes-lues'))->assertRedirect();

    expect($client->user->unreadNotifications()->count())->toBe(0);
});

test('nobody can read or open the notification of another account', function () {
    $proprietaire = Client::factory()->create();
    $autre = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $proprietaire->id, 'montant_negocie' => 90000]);
    Notification::sendNow($proprietaire->user, new DevisRecuNotification($demande));
    $notification = $proprietaire->user->notifications()->firstOrFail();

    $this->actingAs($autre->user)
        ->post(route('notifications.lire', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('the apprenant space also has its bell', function () {
    $apprenant = Apprenant::factory()->create();
    Notification::sendNow($apprenant->user, new AlerteEquipeNotification('test', 'Bienvenue', 'Votre espace est prêt.', url('/espace-apprenant')));

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.dashboard'))
        ->assertInertia(fn ($page) => $page->where('cloche.nonLues', 1));
});

test('team members get the alerts in the admin bell and can open them', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $client = Client::factory()->create();

    Notifier::envoyer($membre, new AlerteEquipeNotification('demande.nouvelle', 'Nouvelle demande', 'Un client a demandé un service.', route('espace-admin.demandes.index')));

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('cloche.nonLues', 1)
            ->where('cloche.recentes.0.titre', 'Nouvelle demande'));

    $notification = $membre->notifications()->firstOrFail();

    $this->actingAs($membre, 'admin')
        ->post(route('espace-admin.notifications.lire', $notification->id))
        ->assertRedirect('/espace-admin/demandes');

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.notifications.toutes-lues'))->assertRedirect();
    expect($membre->unreadNotifications()->count())->toBe(0);

    $this->actingAs($client->user, 'web')->get(route('espace-client.dashboard'))
        ->assertInertia(fn ($page) => $page->where('cloche.nonLues', 0));
});

test('a notification link pointing outside the application is never followed', function () {
    $client = Client::factory()->create();
    $client->user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => AlerteEquipeNotification::class,
        'data' => ['titre' => 'Piège', 'message' => 'x', 'url' => 'https://exemple.test/hameçon'],
    ]);
    $notification = $client->user->notifications()->firstOrFail();

    $reponse = $this->actingAs($client->user)
        ->post(route('notifications.lire', $notification->id))
        ->assertRedirect();

    expect($reponse->headers->get('Location'))->not->toContain('exemple.test');
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('the bell stays available on pages that have their own notifications data', function () {
    $directrice = MembreEquipe::factory()->create(['role' => MembreEquipeRole::Promotrice]);
    $client = Client::factory()->create();

    $this->actingAs($directrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'notifications']))
        ->assertInertia(fn ($page) => $page->has('cloche.nonLues')->has('cloche.recentes'));

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.parametres'))
        ->assertInertia(fn ($page) => $page->has('cloche.nonLues')->has('notifications'));
});
