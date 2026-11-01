<?php

use App\Models\Apprenant;
use App\Models\Client;

test('the apprenant profil page exposes the current name and email', function () {
    $apprenant = Apprenant::factory()->create();
    $apprenant->user->update(['name' => 'Stéphanie A.', 'email' => 'stephanie@example.com']);

    $response = $this->actingAs($apprenant->user)->get(route('espace-apprenant.profil'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/apprenant/profil')
        ->where('profil.nom', 'Stéphanie A.')
        ->where('profil.email', 'stephanie@example.com'));
});

test('an apprenant can update their name and email', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.profil.update'), [
        'nom' => 'Nouveau Nom',
        'email' => 'nouveau@example.com',
    ]);

    $response->assertRedirect(route('espace-apprenant.profil'));

    $apprenant->user->refresh();
    expect($apprenant->user->name)->toBe('Nouveau Nom');
    expect($apprenant->user->email)->toBe('nouveau@example.com');
    expect($apprenant->user->email_verified_at)->toBeNull();
});

test('an apprenant can update their telephone, ville and date de naissance', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.profil.update'), [
        'nom' => $apprenant->user->name,
        'email' => $apprenant->user->email,
        'telephone' => '+237698000000',
        'ville' => 'Yaoundé',
        'date_naissance' => '1999-01-01',
    ]);

    $response->assertRedirect(route('espace-apprenant.profil'));

    $apprenant->refresh();
    expect($apprenant->telephone)->toBe('+237698000000');
    expect($apprenant->ville)->toBe('Yaoundé');
    expect($apprenant->date_naissance->toDateString())->toBe('1999-01-01');
});

test('an apprenant can update their notification preferences', function () {
    $apprenant = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.profil.notifications'), [
        'notif_seances' => false,
        'notif_candidatures' => true,
        'notif_newsletter' => true,
    ]);

    $response->assertRedirect(route('espace-apprenant.profil'));

    $apprenant->user->refresh();
    expect($apprenant->user->notif_seances)->toBeFalse();
    expect($apprenant->user->notif_candidatures)->toBeTrue();
    expect($apprenant->user->notif_newsletter)->toBeTrue();
});

test('the profil update rejects an email already used by another account', function () {
    $apprenant = Apprenant::factory()->create();
    $autre = Apprenant::factory()->create();

    $response = $this->actingAs($apprenant->user)->patch(route('espace-apprenant.profil.update'), [
        'nom' => 'Test',
        'email' => $autre->user->email,
    ]);

    $response->assertSessionHasErrors('email');
});

test('a client cannot reach the apprenant profil page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)->get(route('espace-apprenant.profil'))->assertForbidden();
});
