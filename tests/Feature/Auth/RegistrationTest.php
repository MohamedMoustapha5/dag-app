<?php

use App\Enums\TeamRole;
use App\Enums\UserType;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
    );
});

test('a particulier registers as a client and lands on the espace client', function () {
    $response = $this->post(route('register.store'), [
        'compte_type' => 'particulier',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->type)->toBe(UserType::Client);
    expect($user->client)->not->toBeNull();
    expect($user->client->type)->toBe('PARTICULIER');
    $response->assertRedirect(route('espace-client.dashboard'));
});

test('an entreprise registers as an entreprise-type client', function () {
    $this->post(route('register.store'), [
        'compte_type' => 'entreprise',
        'name' => 'Fatou Boutique',
        'email' => 'fatou@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $client = Client::whereHas('user', fn ($q) => $q->where('email', 'fatou@example.com'))->first();

    expect($client)->not->toBeNull();
    expect($client->type)->toBe('ENTREPRISE');
    expect($client->nom)->toBe('Fatou Boutique');
});

test('an apprenant registers with an apprenant profile and lands on the espace apprenant', function () {
    $response = $this->post(route('register.store'), [
        'compte_type' => 'apprenant',
        'name' => 'Stéphanie A.',
        'email' => 'stephanie@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'stephanie@example.com')->first();

    expect($user->type)->toBe(UserType::Apprenant);
    expect(Apprenant::where('user_id', $user->id)->exists())->toBeTrue();
    expect($user->client)->toBeNull();
    $response->assertRedirect(route('espace-apprenant.dashboard'));
});

test('registration requires a known compte_type', function () {
    $response = $this->post(route('register.store'), [
        'compte_type' => 'entreprise-partenaire',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('compte_type');
    $this->assertGuest();
});
