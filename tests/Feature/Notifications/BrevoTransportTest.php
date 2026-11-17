<?php

use App\Models\User;
use App\Support\Messagerie\BrevoTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

test('an e-mail of the application goes out through the Brevo API', function () {
    config(['mail.default' => 'brevo', 'services.brevo.key' => 'cle-de-test']);
    Http::preventStrayRequests();
    Http::fake([BrevoTransport::ENDPOINT => Http::response(['messageId' => '<abc@smtp-relay.brevo.com>'], 201)]);
    $user = User::factory()->create(['name' => 'Awa Diallo', 'email' => 'awa@example.com']);

    $user->sendPasswordResetNotification('jeton-123');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->url() === BrevoTransport::ENDPOINT
        && $request->hasHeader('api-key', 'cle-de-test')
        && $request['sender'] === ['email' => config('mail.from.address'), 'name' => config('mail.from.name')]
        && $request['to'] === [['email' => 'awa@example.com']]
        && $request['subject'] === 'Reset your password'
        && str_contains($request['htmlContent'], 'jeton-123')
        && str_contains($request['textContent'], 'jeton-123'));
});

test('copies, reply-to and attachments are passed to Brevo', function () {
    config(['services.brevo.key' => 'cle-de-test']);
    Http::preventStrayRequests();
    Http::fake([BrevoTransport::ENDPOINT => Http::response(['messageId' => '<abc@smtp-relay.brevo.com>'], 201)]);

    Mail::mailer('brevo')->raw('Bonjour', fn ($message) => $message
        ->to('awa@example.com', 'Awa Diallo')
        ->cc('copie@example.com')
        ->bcc('cachee@example.com')
        ->replyTo('equipe@example.com', 'Équipe DAG')
        ->subject('Votre devis')
        ->attachData('contenu du devis', 'devis.pdf'));

    Http::assertSent(fn (Request $request) => $request['to'] === [['email' => 'awa@example.com', 'name' => 'Awa Diallo']]
        && $request['cc'] === [['email' => 'copie@example.com']]
        && $request['bcc'] === [['email' => 'cachee@example.com']]
        && $request['replyTo'] === ['email' => 'equipe@example.com', 'name' => 'Équipe DAG']
        && $request['subject'] === 'Votre devis'
        && $request['textContent'] === 'Bonjour'
        && ! isset($request['htmlContent'])
        && $request['attachment'] === [['name' => 'devis.pdf', 'content' => base64_encode('contenu du devis')]]);
});

test('an e-mail Brevo refuses fails with Brevo\'s reason', function () {
    config(['services.brevo.key' => 'cle-invalide']);
    Http::preventStrayRequests();
    Http::fake([BrevoTransport::ENDPOINT => Http::response(['code' => 'unauthorized', 'message' => 'Key not found'], 401)]);

    $envoi = fn () => Mail::mailer('brevo')->raw('Bonjour', fn ($message) => $message->to('awa@example.com')->subject('Test'));

    expect($envoi)->toThrow(TransportException::class, 'Brevo a refusé l’e-mail (401) : Key not found');
});

test('without an API key nothing is sent to Brevo', function () {
    config(['services.brevo.key' => null]);
    Http::preventStrayRequests();
    Http::fake();

    $envoi = fn () => Mail::mailer('brevo')->raw('Bonjour', fn ($message) => $message->to('awa@example.com')->subject('Test'));

    expect($envoi)->toThrow(TransportException::class, 'BREVO_API_KEY');
    Http::assertNothingSent();
});
