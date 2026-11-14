<?php

use App\Models\User;

test('validation errors follow the language chosen with the switcher', function (?string $langue, string $message) {
    if ($langue !== null) {
        $this->withUnencryptedCookie('dag_lang', $langue);
    }

    $response = $this->post(route('login.store'), ['email' => '', 'password' => 'secret']);

    $response->assertSessionHasErrors(['email' => $message]);
})->with([
    'no choice yet' => [null, 'Le champ adresse e-mail est obligatoire.'],
    'French' => ['fr', 'Le champ adresse e-mail est obligatoire.'],
    'English' => ['en', 'The email address field is required.'],
]);

test('sign-in errors follow the language chosen with the switcher', function (string $langue, string $message) {
    $user = User::factory()->create();
    $this->withUnencryptedCookie('dag_lang', $langue);

    $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

    $response->assertSessionHasErrors(['email' => $message]);
    $this->assertGuest();
})->with([
    'French' => ['fr', 'Ces identifiants ne correspondent à aucun compte.'],
    'English' => ['en', 'These credentials do not match our records.'],
]);

test('an unknown language falls back to French', function () {
    $this->withUnencryptedCookie('dag_lang', 'de');

    $response = $this->post(route('login.store'), ['email' => '', 'password' => 'secret']);

    $response->assertSessionHasErrors(['email' => 'Le champ adresse e-mail est obligatoire.']);
});
