<?php

use App\Models\AuditRequest;
use App\Models\Client;
use App\Models\MembreEquipe;
use App\Models\TicketSupport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'services.recaptcha.site_key' => 'cle-du-site',
        'services.recaptcha.secret_key' => 'cle-secrete',
    ]);
    Http::preventStrayRequests();
});

/**
 * Fakes Google's siteverify answer.
 *
 * @param  array<string, mixed>  $reponse
 */
function fakeGoogleRecaptcha(array $reponse): void
{
    Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response($reponse)]);
}

/**
 * @return array<string, string>
 */
function messageDeContact(array $champs = []): array
{
    return [
        'nom' => 'Karim Benali',
        'email' => 'karim@example.com',
        'sujet' => 'Demande de devis',
        'message' => 'Bonjour, je souhaite un devis pour un site vitrine.',
        ...$champs,
    ];
}

test('every public form is refused without the reCAPTCHA box ticked', function (string $route) {
    $this->post(route($route), ['email' => 'visiteur@example.com', 'password' => 'password'])
        ->assertSessionHasErrors(['recaptcha' => 'Cochez la case « Je ne suis pas un robot ».']);

    Http::assertNothingSent();
})->with([
    'connexion' => 'login.store',
    'inscription' => 'register.store',
    'mot de passe oublié' => 'password.email',
    'connexion équipe' => 'admin.connexion.store',
    'contact' => 'contact.store',
    'audit gratuit' => 'audit-requests.store',
    'candidature spontanée' => 'candidatures-spontanees.store',
]);

test('a form whose token Google confirms goes through, and the token is checked with the secret key', function () {
    fakeGoogleRecaptcha(['success' => true, 'hostname' => 'agencydag.com']);

    $this->post(route('contact.store'), messageDeContact(['recaptcha' => 'jeton-valide']))
        ->assertSessionHasNoErrors();

    expect(TicketSupport::where('email', 'karim@example.com')->exists())->toBeTrue();
    Http::assertSent(fn (Request $requete) => $requete->url() === 'https://www.google.com/recaptcha/api/siteverify'
        && $requete['secret'] === 'cle-secrete'
        && $requete['response'] === 'jeton-valide'
        && $requete['remoteip'] === '127.0.0.1');
});

test('a token Google rejects (expired, reused or forged) is refused', function () {
    fakeGoogleRecaptcha(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

    $this->post(route('contact.store'), messageDeContact(['recaptcha' => 'jeton-perime']))
        ->assertSessionHasErrors(['recaptcha' => 'La vérification « Je ne suis pas un robot » a expiré ou échoué : cochez de nouveau la case.']);

    expect(TicketSupport::count())->toBe(0);
});

test('the reCAPTCHA check runs before the sign-in, which then proceeds normally', function () {
    fakeGoogleRecaptcha(['success' => true]);
    $client = Client::factory()->create();

    $this->post(route('login.store'), ['email' => $client->user->email, 'password' => 'password', 'recaptcha' => 'jeton-valide'])
        ->assertRedirect(route('espace-client.dashboard'));

    $this->assertAuthenticatedAs($client->user);
});

test('the team sign-in is refused when Google rejects the token', function () {
    fakeGoogleRecaptcha(['success' => false]);
    $membre = MembreEquipe::factory()->create();

    $this->post(route('admin.connexion.store'), ['email' => $membre->email, 'password' => 'password', 'recaptcha' => 'jeton-robot'])
        ->assertSessionHasErrors('recaptcha');

    $this->assertGuest('admin');
});

test('Google being unreachable does not lock visitors out', function () {
    Log::spy();
    Http::fake(['www.google.com/*' => Http::failedConnection()]);

    $this->post(route('audit-requests.store'), ['page_url' => 'https://exemple-client.com', 'email' => 'lead@example.com', 'recaptcha' => 'jeton'])
        ->assertSessionHasNoErrors();

    expect(AuditRequest::where('email', 'lead@example.com')->exists())->toBeTrue();
    Log::shouldHaveReceived('warning')->once();
});

test('a secret key Google refuses blocks the form and is logged as a configuration error', function () {
    Log::spy();
    fakeGoogleRecaptcha(['success' => false, 'error-codes' => ['invalid-input-secret']]);

    $this->post(route('contact.store'), messageDeContact(['recaptcha' => 'jeton']))
        ->assertSessionHasErrors('recaptcha');

    expect(TicketSupport::count())->toBe(0);
    Log::shouldHaveReceived('error')->once();
});

test('the widget key is shared with the pages only while both keys are set', function (?string $cleSecrete, ?string $cleAttendue) {
    config(['services.recaptcha.secret_key' => $cleSecrete]);

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('recaptchaSiteKey', $cleAttendue));
})->with([
    'both keys' => ['cle-secrete', 'cle-du-site'],
    'secret key missing' => [null, null],
]);

test('without keys, reCAPTCHA is off and forms go through without a token', function () {
    config(['services.recaptcha.site_key' => null, 'services.recaptcha.secret_key' => null]);

    $this->post(route('contact.store'), messageDeContact())
        ->assertSessionHasNoErrors();

    expect(TicketSupport::count())->toBe(1);
    Http::assertNothingSent();
});
