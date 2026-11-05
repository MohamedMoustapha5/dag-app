<?php

use App\Models\Client;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('a client changes their email with their current password and must verify the new address', function () {
    Notification::fake();
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->patch(route('espace-client.parametres.update'), [
            'nom' => $client->nom,
            'email' => 'Nouvelle@Example.com',
            'current_password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('espace-client.parametres'));

    $user = $client->user->fresh();
    expect($user->email)->toBe('nouvelle@example.com');
    expect($user->email_verified_at)->toBeNull();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a client cannot change their email without the right current password', function (?string $motDePasse) {
    $client = Client::factory()->create();
    $email = $client->user->email;

    $this->actingAs($client->user)
        ->patch(route('espace-client.parametres.update'), [
            'nom' => $client->nom,
            'email' => 'pirate@example.com',
            'current_password' => $motDePasse,
        ])
        ->assertSessionHasErrors('current_password');

    expect($client->user->fresh()->email)->toBe($email);
})->with(['missing' => null, 'wrong' => 'mauvais']);

test('a client keeps updating their name without a password when the email stays the same', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->patch(route('espace-client.parametres.update'), [
            'nom' => 'Nouveau nom',
            'email' => $client->user->email,
        ])
        ->assertSessionHasNoErrors();

    expect($client->fresh()->nom)->toBe('Nouveau nom');
    expect($client->user->fresh()->email_verified_at)->not->toBeNull();
});
