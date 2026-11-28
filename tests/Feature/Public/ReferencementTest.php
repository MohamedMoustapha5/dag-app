<?php

use App\Models\Formation;
use App\Models\MembreEquipe;

test('a public page gives search engines its own title, description, canonical address and preview', function () {
    $response = $this->get(route('catalogue'))->assertOk();

    $response->assertSee('<title>Services digitaux : marketing, sites web, branding - Digital Agency Group</title>', false);
    $response->assertSee('<meta name="description" content="Création de contenu, community management', false);
    $response->assertSee('<link rel="canonical" href="'.route('catalogue').'">', false);
    $response->assertSee('<meta property="og:image" content="'.url('/images/brand/partage.jpg').'">', false);
    $response->assertDontSee('noindex', false);
});

test('the home page describes the agency to Google, address included', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('<title>Digital Agency Group — Agence digitale à Douala, Cameroun</title>', false);
    $response->assertSee('"@type":"ProfessionalService"', false);
    $response->assertSee('"addressLocality":"Douala"', false);
});

test('a formation page is titled after the formation and described by its own text', function () {
    $formation = Formation::factory()->create(['nom' => 'Community management avancé', 'description' => 'Animer une communauté et mesurer ses résultats.']);

    $response = $this->get(route('formations.show', $formation))->assertOk();

    $response->assertSee('<title>Formation Community management avancé - Digital Agency Group</title>', false);
    $response->assertSee('<meta name="description" content="Animer une communauté et mesurer ses résultats.">', false);
    $response->assertSee('"@type":"Course"', false);
});

test('private screens are kept out of search results', function () {
    $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->get(route('espace-admin.equipe.index'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('<meta name="description"', false);
});

test('the sitemap lists the public pages and the published formations only', function () {
    $publiee = Formation::factory()->create();
    $brouillon = Formation::factory()->create(['statut' => 'BROUILLON']);

    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $response->assertSee('<loc>'.route('home').'</loc>', false);
    $response->assertSee('<loc>'.route('mentions-legales').'</loc>', false);
    $response->assertSee('<loc>'.route('formations.show', $publiee).'</loc>', false);
    $response->assertDontSee(route('formations.show', $brouillon), false);
    $response->assertDontSee('espace-', false);
});

test('robots.txt keeps crawlers out of the espaces and points to the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /espace-admin')
        ->assertSee('Sitemap: '.route('sitemap'));
});

test('the home page carries the Search Console code once it is configured', function () {
    $this->get(route('home'))->assertDontSee('google-site-verification', false);

    config(['services.google_search_console' => 'code-de-verification']);

    $this->get(route('home'))->assertSee('<meta name="google-site-verification" content="code-de-verification">', false);
    $this->get(route('catalogue'))->assertDontSee('google-site-verification', false);
});
