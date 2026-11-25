<?php

test('every response carries the browser security headers', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'self'");
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('error pages carry the security headers too', function () {
    $response = $this->get('/page-qui-n-existe-pas');

    $response->assertNotFound();
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('HTTPS is enforced for a year once the site is reached over HTTPS', function () {
    $response = $this->get(str_replace('http://', 'https://', url('/')));

    $response->assertOk();
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('HTTPS is not enforced over plain HTTP', function () {
    $response = $this->get(str_replace('https://', 'http://', url('/')));

    $response->assertOk();
    $response->assertHeaderMissing('Strict-Transport-Security');
});
