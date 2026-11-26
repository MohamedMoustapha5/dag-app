<?php

use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use App\Notifications\AccesEquipeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config(['entreprise.administrateur.email' => 'admin@exemple.com']);
    Notification::fake();
});

/** The password the member was e-mailed, checked against the stored one. */
function motDePasseEnvoye(MembreEquipe $membre): string
{
    $notification = Notification::sent($membre, AccesEquipeNotification::class)->sole();

    expect(Hash::check($notification->motDePasse, $membre->fresh()->password))->toBeTrue();

    return $notification->motDePasse;
}

test('the administration account is created with every right, hidden from the site, and receives its password', function () {
    $this->artisan('equipe:envoyer-acces')->assertSuccessful();

    $administrateur = MembreEquipe::where('email', 'admin@exemple.com')->sole();
    expect($administrateur->role)->toBe(MembreEquipeRole::Promotrice);
    expect($administrateur->visible_sur_le_site)->toBeFalse();

    $motDePasse = motDePasseEnvoye($administrateur);
    expect(Notification::sent($administrateur, AccesEquipeNotification::class)->sole()->administrateur)->toBeTrue();

    $this->post(route('admin.connexion.store'), ['email' => 'admin@exemple.com', 'password' => $motDePasse])
        ->assertRedirect();
    $this->assertAuthenticatedAs($administrateur, 'admin');

    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('equipe', []));
});

test('an existing account at the administration address is given every right instead of a duplicate', function () {
    $existant = MembreEquipe::factory()->developpeur()->create(['email' => 'admin@exemple.com', 'name' => 'Mustapha']);

    $this->artisan('equipe:envoyer-acces')->assertSuccessful();

    expect(MembreEquipe::count())->toBe(1);
    expect($existant->fresh()->role)->toBe(MembreEquipeRole::Promotrice);
    expect($existant->fresh()->name)->toBe('Mustapha');
    expect(Hash::check('password', $existant->fresh()->password))->toBeFalse();
    motDePasseEnvoye($existant);
});

test('with --tous every active member gets their own password, a deactivated one nothing', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $inactif = MembreEquipe::factory()->inactif()->create();

    $this->artisan('equipe:envoyer-acces --tous')->assertSuccessful();

    expect(motDePasseEnvoye($juriste))
        ->not->toBe(motDePasseEnvoye(MembreEquipe::where('email', 'admin@exemple.com')->sole()));
    expect($juriste->fresh()->role)->toBe(MembreEquipeRole::Juriste);
    Notification::assertNothingSentTo($inactif);
});

test('one member can be sent a new password, an unknown address is refused', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();

    $this->artisan('equipe:envoyer-acces', ['email' => $juriste->email])->assertSuccessful();
    motDePasseEnvoye($juriste);
    expect(MembreEquipe::where('email', 'admin@exemple.com')->exists())->toBeFalse();

    $this->artisan('equipe:envoyer-acces', ['email' => 'inconnu@exemple.com'])->assertFailed();
});

test('the password does not change when its e-mail cannot leave', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    Notification::shouldReceive('sendNow')->andThrow(new RuntimeException('Brevo injoignable'));

    $this->artisan('equipe:envoyer-acces', ['email' => $juriste->email])->assertFailed();

    expect(Hash::check('password', $juriste->fresh()->password))->toBeTrue();
});
