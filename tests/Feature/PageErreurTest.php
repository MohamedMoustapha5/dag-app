<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('a missing page shows the site error page', function () {
    $response = $this->get('/page-qui-n-existe-pas');

    $response->assertNotFound();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('erreur')
        ->where('statut', 404),
    );
});

test('a server error shows the site error page when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/erreur-de-test', fn () => throw new RuntimeException('Panne'));

    $response = $this->get('/erreur-de-test');

    $response->assertInternalServerError();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('erreur')
        ->where('statut', 500),
    );
});

test('a server error keeps the debug page while debug is on', function () {
    config(['app.debug' => true]);
    Route::get('/erreur-de-test', fn () => throw new RuntimeException('Panne'));

    $response = $this->get('/erreur-de-test');

    $response->assertInternalServerError();
    $response->assertSee('Panne');
    $response->assertDontSee('"component":"erreur"', false);
});

test('a JSON request keeps the JSON error', function () {
    $response = $this->getJson('/page-qui-n-existe-pas');

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});
