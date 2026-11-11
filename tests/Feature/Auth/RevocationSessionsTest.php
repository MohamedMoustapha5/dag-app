<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Password;

/*
 * A password change (settings, administration) or a password reset revokes
 * every other session of the account and its "remember me" cookies; the
 * device that made the change stays signed in.
 *
 * Two devices are simulated: the session of the "other device" is kept the
 * way its browser would keep it (signed-in account and password fingerprint),
 * put aside while the test acts elsewhere, then replayed.
 */

/**
 * Signs the account in on a device, visits a page, and returns that
 * device's session as its browser keeps it.
 *
 * @return array<string, mixed>
 */
function sessionDUnAppareil(mixed $test, Authenticatable $compte, string $garde, string $page): array
{
    changerDAppareil($test);

    $test->actingAs($compte, $garde)->get($page)->assertOk();

    return [
        ...session()->all(),
        auth()->guard($garde)->getName() => $compte->getAuthIdentifier(),
    ];
}

/**
 * A fresh browser: no session, nobody signed in.
 *
 * @param  array<string, mixed>  $session  the session to replay, if any
 */
function changerDAppareil(mixed $test, array $session = []): void
{
    auth()->forgetGuards();
    auth()->shouldUse('web');
    $test->flushSession();

    if ($session !== []) {
        $test->withSession($session);
    }
}

test('changing the password in the settings signs out the other devices and keeps this one signed in', function () {
    $user = Client::factory()->create()->user;
    $jetonAvant = $user->remember_token;
    $autreAppareil = sessionDUnAppareil($this, $user, 'web', route('espace-client.dashboard'));

    changerDAppareil($this);
    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'Nouveau-mot-de-passe-2026',
            'password_confirmation' => 'Nouveau-mot-de-passe-2026',
        ])
        ->assertSessionHasNoErrors();
    $this->get(route('espace-client.dashboard'))->assertOk();

    expect($user->fresh()->remember_token)->not->toBe($jetonAvant);

    changerDAppareil($this, $autreAppareil);
    $this->get(route('espace-client.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest('web');
});

test('a password reset signs out every open session and every remembered device', function () {
    $user = Client::factory()->create()->user;
    $user->update(['remember_token' => 'jeton-memorise-avant']);
    $cookieSouvenir = $user->id.'|jeton-memorise-avant|'.$user->getAuthPassword();
    $autreAppareil = sessionDUnAppareil($this, $user->fresh(), 'web', route('espace-client.dashboard'));

    changerDAppareil($this);
    $this->post(route('password.update'), [
        'token' => Password::broker()->createToken($user),
        'email' => $user->email,
        'password' => 'Nouveau-mot-de-passe-2026',
        'password_confirmation' => 'Nouveau-mot-de-passe-2026',
    ])->assertSessionHasNoErrors();

    changerDAppareil($this, $autreAppareil);
    $this->get(route('espace-client.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest('web');

    changerDAppareil($this);
    $this->withCookie(auth()->guard('web')->getRecallerName(), $cookieSouvenir)
        ->get(route('espace-client.dashboard'))
        ->assertRedirect(route('login'));
    $this->assertGuest('web');
});

test('a team member changing their password signs out their other administration sessions', function () {
    $membre = MembreEquipe::factory()->create();
    $jetonAvant = $membre->remember_token;
    $autreAppareil = sessionDUnAppareil($this, $membre, 'admin', route('espace-admin.dashboard'));

    changerDAppareil($this);
    $this->actingAs($membre, 'admin')
        ->put(route('espace-admin.mot-de-passe.update'), [
            'current_password' => 'password',
            'password' => 'Nouveau-mot-de-passe-2026',
            'password_confirmation' => 'Nouveau-mot-de-passe-2026',
        ])
        ->assertSessionHasNoErrors();
    $this->get(route('espace-admin.dashboard'))->assertOk();

    expect($membre->fresh()->remember_token)->not->toBe($jetonAvant);

    changerDAppareil($this, $autreAppareil);
    $this->get(route('espace-admin.dashboard'))->assertRedirect(route('admin.connexion'));
    $this->assertGuest('admin');
});

test('a session opened before the password changed is signed out on every route, sensitive ones included', function (string $methode, Closure $url, Closure $aucunEffet) {
    $client = Client::factory()->create();
    $user = $client->user;
    $this->facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 50000, 'montant_paye' => 0]);
    $this->invitation = TeamInvitation::factory()->create(['team_id' => Team::factory()->create()->id, 'email' => $user->email]);

    // The other device even confirmed the password a moment ago.
    $autreAppareil = [
        ...sessionDUnAppareil($this, $user, 'web', route('espace-client.dashboard')),
        'auth.password_confirmed_at' => time(),
    ];

    $user->update(['password' => 'Change-depuis-un-autre-appareil-1']);

    changerDAppareil($this, $autreAppareil);
    $this->call($methode, $url($this, $user), ['moyen' => 'OM', 'telephone' => '699000000'])
        ->assertRedirect(route('login'));
    $this->assertGuest('web');

    $aucunEffet($this, $user->fresh());
})->with([
    'lire le secret de double authentification' => ['GET', fn () => route('two-factor.secret-key'), fn () => null],
    'lire les codes de secours' => ['GET', fn () => route('two-factor.recovery-codes'), fn () => null],
    'activer la double authentification' => ['POST', fn () => route('two-factor.enable'), fn ($t, User $u) => expect($u->two_factor_secret)->toBeNull()],
    'préparer une passkey' => ['GET', fn () => route('passkey.registration-options'), fn () => null],
    'payer une facture' => ['POST', fn ($t) => route('paiements.initier', $t->facture), fn ($t) => expect($t->facture->paiements()->count())->toBe(0)],
    'accepter une invitation d’équipe' => ['POST', fn ($t) => route('invitations.accept', $t->invitation), fn ($t) => expect($t->invitation->fresh()->accepted_at)->toBeNull()],
    'marquer les notifications lues' => ['POST', fn () => route('notifications.toutes-lues'), fn () => null],
    'tableau de bord d’équipe' => ['GET', fn ($t, User $u) => route('dashboard', ['current_team' => $u->personalTeam()]), fn () => null],
]);
