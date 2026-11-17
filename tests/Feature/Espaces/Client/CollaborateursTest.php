<?php

use App\Enums\UserType;
use App\Models\Client;
use App\Models\ClientCollaborateur;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\User;
use App\Notifications\ContratValideNotification;
use App\Notifications\InvitationCollaborateurNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function entreprise(): Client
{
    return Client::factory()->create(['type' => 'ENTREPRISE', 'nom' => 'Batiplus SARL']);
}

function lienInvitation(ClientCollaborateur $collaborateur): string
{
    return URL::temporarySignedRoute('collaborateurs.invitation', now()->addDays(7), ['collaborateur' => $collaborateur->id], absolute: false);
}

test('the holder of an entreprise or organisation sees who has access to its espace', function (string $type) {
    $client = Client::factory()->create(['type' => $type]);
    $collaborateur = ClientCollaborateur::factory()->create(['client_id' => $client->id]);

    $response = $this->actingAs($client->user)->get(route('espace-client.collaborateurs.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/client/collaborateurs')
        ->where('titulaire.email', $client->user->email)
        ->where('collaborateurs.0.email', $collaborateur->user->email)
        ->where('collaborateurs.0.actif', true)
        ->where('gestionAcces', true));
})->with(['ENTREPRISE', 'ORGANISATION']);

test('neither a particulier nor a collaborator can manage the accesses', function () {
    $particulier = Client::factory()->create(['type' => 'PARTICULIER']);
    $collaborateur = ClientCollaborateur::factory()->create();

    $this->actingAs($particulier->user)->get(route('espace-client.collaborateurs.index'))->assertForbidden();
    $this->actingAs($collaborateur->user)->get(route('espace-client.collaborateurs.index'))->assertForbidden();
    $this->actingAs($collaborateur->user)
        ->post(route('espace-client.collaborateurs.store'), ['nom' => 'Intrus', 'email' => 'intrus@example.com'])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'intrus@example.com']);
});

test('the holder invites a colleague, who receives a link to choose their password', function () {
    Notification::fake();
    $client = entreprise();

    $this->actingAs($client->user)
        ->post(route('espace-client.collaborateurs.store'), ['nom' => 'Awa Diallo', 'email' => 'Awa@Batiplus.cm'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user = User::where('email', 'awa@batiplus.cm')->firstOrFail();
    expect($user->type)->toBe(UserType::Client);
    expect($user->email_verified_at)->toBeNull();
    $collaborateur = $user->collaboration;
    expect($collaborateur->client_id)->toBe($client->id);
    expect($collaborateur->invite_par_id)->toBe($client->user_id);
    expect($collaborateur->estActif())->toBeFalse();
    Notification::assertSentTo($user, InvitationCollaborateurNotification::class, function (InvitationCollaborateurNotification $invitation) use ($user) {
        return str_contains($invitation->url($user), '/invitation-entreprise/') && str_contains($invitation->url($user), 'signature=');
    });
});

test('an address that already has a DAG account cannot be invited', function () {
    $client = entreprise();
    $autre = User::factory()->create(['email' => 'deja@example.com']);

    $this->actingAs($client->user)
        ->post(route('espace-client.collaborateurs.store'), ['nom' => 'Déjà', 'email' => 'deja@example.com'])
        ->assertSessionHasErrors(['email' => 'Cette adresse e-mail a déjà un compte DAG. Invitez une autre adresse.']);

    expect($autre->fresh()->collaboration)->toBeNull();
});

test('the colleague chooses a password from the link and enters the espace of the entreprise', function () {
    $collaborateur = ClientCollaborateur::factory()->enAttente()->create();
    $collaborateur->user->forceFill(['email_verified_at' => null])->save();
    $lien = lienInvitation($collaborateur);

    $this->get($lien)->assertOk()->assertInertia(fn ($page) => $page
        ->component('auth/invitation-collaborateur')
        ->where('entreprise', $collaborateur->client->nom)
        ->where('dejaActive', false));

    $this->post($lien, ['password' => 'MotDePasse2026', 'password_confirmation' => 'MotDePasse2026'])
        ->assertRedirect(route('espace-client.dashboard'));

    $this->assertAuthenticatedAs($collaborateur->user);
    expect($collaborateur->fresh()->estActif())->toBeTrue();
    expect($collaborateur->user->fresh()->email_verified_at)->not->toBeNull();
    $this->get(route('espace-client.dashboard'))->assertOk();
});

test('the invitation link must be signed and works only once', function () {
    $collaborateur = ClientCollaborateur::factory()->enAttente()->create();

    $this->get(route('collaborateurs.invitation', $collaborateur))->assertForbidden();

    $collaborateur->update(['accepte_le' => now()]);
    $motDePasse = $collaborateur->user->password;

    $this->post(lienInvitation($collaborateur), ['password' => 'Autre2026xyz', 'password_confirmation' => 'Autre2026xyz'])
        ->assertRedirect(route('login'));

    expect($collaborateur->user->fresh()->password)->toBe($motDePasse);
    $this->assertGuest();
});

test('a collaborator works on the records of the entreprise and of no other client', function () {
    $collaborateur = ClientCollaborateur::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $collaborateur->client_id]);
    $autre = Demande::factory()->create();

    $this->actingAs($collaborateur->user)->get(route('espace-client.demandes.show', $demande))->assertOk();
    $this->actingAs($collaborateur->user)->get(route('espace-client.demandes.show', $autre))->assertForbidden();
});

test('the holder removes an access: the colleague account is closed, the records stay', function () {
    $collaborateur = ClientCollaborateur::factory()->create();
    $client = $collaborateur->client;
    $facture = Facture::factory()->create(['client_id' => $client->id]);
    $userId = $collaborateur->user_id;

    $this->actingAs($client->user)
        ->delete(route('espace-client.collaborateurs.destroy', $collaborateur))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertModelMissing($collaborateur);
    $this->assertModelExists($client);
    $this->assertModelExists($facture);
});

test('a holder cannot remove or reinvite the colleague of another client', function () {
    $client = entreprise();
    $autre = ClientCollaborateur::factory()->enAttente()->create();

    $this->actingAs($client->user)->delete(route('espace-client.collaborateurs.destroy', $autre))->assertNotFound();
    $this->actingAs($client->user)->post(route('espace-client.collaborateurs.renvoyer', $autre))->assertNotFound();

    $this->assertModelExists($autre);
});

test('a pending invitation can be sent again, an active access cannot', function () {
    Notification::fake();
    $client = entreprise();
    $enAttente = ClientCollaborateur::factory()->enAttente()->create(['client_id' => $client->id]);
    $actif = ClientCollaborateur::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)->post(route('espace-client.collaborateurs.renvoyer', $enAttente))->assertRedirect();
    $this->actingAs($client->user)->post(route('espace-client.collaborateurs.renvoyer', $actif))->assertConflict();

    Notification::assertSentTo($enAttente->user, InvitationCollaborateurNotification::class);
    Notification::assertNotSentTo($actif->user, InvitationCollaborateurNotification::class);
});

test('active collaborators are notified like the holder, pending ones are not', function () {
    Notification::fake();
    $client = entreprise();
    $actif = ClientCollaborateur::factory()->create(['client_id' => $client->id]);
    $enAttente = ClientCollaborateur::factory()->enAttente()->create(['client_id' => $client->id]);
    $contrat = Contrat::factory()->create(['client_id' => $client->id]);

    Notifier::envoyer($client->user, new ContratValideNotification($contrat));

    Notification::assertSentTo($client->user, ContratValideNotification::class);
    Notification::assertSentTo($actif->user, ContratValideNotification::class);
    Notification::assertNotSentTo($enAttente->user, ContratValideNotification::class);
});

test('a collaborator closing their account only ends their own access', function () {
    $collaborateur = ClientCollaborateur::factory()->create();
    $client = $collaborateur->client;
    Facture::factory()->create(['client_id' => $client->id]);
    $nom = $client->nom;

    $this->actingAs($collaborateur->user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect();

    $this->assertModelMissing($collaborateur->user);
    expect($client->fresh()->nom)->toBe($nom);
    $this->assertModelExists($client->user);
});

test('closing the account of the entreprise also ends the accesses it gave', function () {
    $collaborateur = ClientCollaborateur::factory()->create();
    $collegue = $collaborateur->user;

    $this->actingAs($collaborateur->client->user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect();

    $this->assertModelMissing($collegue);
});

test('a collaborator updates their own name in the settings, not the name of the entreprise', function () {
    $collaborateur = ClientCollaborateur::factory()->create();
    $client = $collaborateur->client;
    $nomEntreprise = $client->nom;

    $this->actingAs($collaborateur->user)
        ->patch(route('espace-client.parametres.update'), [
            'nom' => 'Awa Diallo',
            'telephone' => '600000000',
            'email' => $collaborateur->user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($collaborateur->user->fresh()->name)->toBe('Awa Diallo');
    expect($client->fresh()->nom)->toBe($nomEntreprise);
    expect($client->fresh()->telephone)->not->toBe('600000000');
});
