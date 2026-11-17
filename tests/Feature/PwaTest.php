<?php

test('every page declares the installable application', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    $response->assertSee('<meta name="theme-color" content="#071f41">', false);
    $response->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false);
});

test('the manifest is installable: name, standalone display and existing icons', function () {
    $manifeste = json_decode((string) file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifeste)
        ->toHaveKey('name', 'DAG — Digital Agency Group')
        ->toHaveKey('short_name', 'DAG')
        ->toHaveKey('start_url', '/')
        ->toHaveKey('display', 'standalone');

    $icones = collect($manifeste['icons']);
    expect($icones->pluck('sizes')->all())->toContain('192x192', '512x512');
    expect($icones->pluck('purpose')->all())->toContain('maskable');

    $icones->each(function (array $icone) {
        $chemin = public_path(ltrim($icone['src'], '/'));
        expect($chemin)->toBeFile();
        expect(getimagesize($chemin)[0].'x'.getimagesize($chemin)[1])->toBe($icone['sizes']);
    });
});

test('the service worker and its offline page are published', function () {
    expect(public_path('sw.js'))->toBeFile();
    expect(public_path('offline.html'))->toBeFile();

    // Pages (account data) are never put in the cache, only static files.
    $script = (string) file_get_contents(public_path('sw.js'));
    expect($script)->toContain("request.mode === 'navigate'")
        ->toContain('fetch(request).catch(() => caches.match(PAGE_HORS_LIGNE))');
});
