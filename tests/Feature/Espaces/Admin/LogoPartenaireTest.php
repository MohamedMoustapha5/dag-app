<?php

use App\Enums\MembreEquipeRole;
use App\Models\LogoPartenaire;
use App\Models\MembreEquipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the public site shows the visible partner logos in their order', function () {
    LogoPartenaire::query()->delete();
    LogoPartenaire::factory()->create(['nom' => 'Second', 'ordre' => 2, 'lien' => 'https://second.cm']);
    LogoPartenaire::factory()->create(['nom' => 'Premier', 'ordre' => 1, 'fond_sombre' => true]);
    LogoPartenaire::factory()->create(['nom' => 'Masqué', 'ordre' => 3, 'visible' => false]);

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->has('partenaires', 2)
        ->where('partenaires.0.nom', 'Premier')
        ->where('partenaires.0.sombre', true)
        ->where('partenaires.1.nom', 'Second')
        ->where('partenaires.1.lien', 'https://second.cm'));

    $this->get(route('a-propos'))->assertInertia(fn ($page) => $page
        ->has('partenaires', 2)
        ->where('stats.partenariats', 2));
});

test('the logos shown until now are carried over to the site', function () {
    expect(LogoPartenaire::query()->ordonnes()->pluck('nom')->all())
        ->toBe(['Nova-Tech', 'Nordex Consulting Group', 'Genis Academic']);
});

test('a membre adds a partner logo and it is published on the site', function () {
    Storage::fake('public');
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);

    $this->actingAs($membre, 'admin')
        ->post(route('espace-admin.logos-partenaires.store'), [
            'nom' => 'Atlas Corp',
            'lien' => 'https://atlas.cm',
            'fond_sombre' => '1',
            'logo' => UploadedFile::fake()->image('atlas.png', 400, 160),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $logo = LogoPartenaire::where('nom', 'Atlas Corp')->sole();
    expect($logo->visible)->toBeTrue()
        ->and($logo->fond_sombre)->toBeTrue()
        ->and($logo->lien)->toBe('https://atlas.cm')
        ->and($logo->ordre)->toBe(LogoPartenaire::max('ordre'))
        ->and($logo->logo)->toStartWith('/storage/logos-partenaires/');
    Storage::disk('public')->assertExists(substr($logo->logo, strlen('/storage/')));
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('partenaires.3.nom', 'Atlas Corp'));
});

test('a partner logo must be a raster image of a reasonable size', function (Closure $fichier, string $champ) {
    Storage::fake('public');
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.logos-partenaires.store'), ['nom' => 'Atlas Corp', 'logo' => $fichier()])
        ->assertSessionHasErrors($champ);

    expect(LogoPartenaire::where('nom', 'Atlas Corp')->exists())->toBeFalse();
})->with([
    'svg' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'logo'],
    'too heavy' => [fn () => UploadedFile::fake()->image('logo.png', 400, 160)->size(3000), 'logo'],
    'too small' => [fn () => UploadedFile::fake()->image('logo.png', 40, 20), 'logo'],
    'missing' => [fn () => null, 'logo'],
]);

test('a partner link must be a web address', function () {
    Storage::fake('public');
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.logos-partenaires.store'), [
            'nom' => 'Atlas Corp',
            'lien' => 'javascript:alert(1)',
            'logo' => UploadedFile::fake()->image('atlas.png', 400, 160),
        ])
        ->assertSessionHasErrors('lien');
});

test('replacing a logo image removes the previous file', function () {
    Storage::fake('public');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    Storage::disk('public')->put('logos-partenaires/ancien.png', 'x');
    $logo = LogoPartenaire::factory()->create(['logo' => '/storage/logos-partenaires/ancien.png']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.logos-partenaires.update', $logo), [
            '_method' => 'patch',
            'nom' => 'Nouveau nom',
            'fond_sombre' => '0',
            'logo' => UploadedFile::fake()->image('nouveau.png', 400, 160),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $logo->refresh();
    expect($logo->nom)->toBe('Nouveau nom')
        ->and($logo->logo)->not->toBe('/storage/logos-partenaires/ancien.png');
    Storage::disk('public')->assertMissing('logos-partenaires/ancien.png');
    Storage::disk('public')->assertExists(substr($logo->logo, strlen('/storage/')));
});

test('a logo can be hidden from the site and shown again', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $logo = LogoPartenaire::where('nom', 'Nova-Tech')->sole();

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.logos-partenaires.visibilite', $logo))->assertRedirect();
    expect($logo->fresh()->visible)->toBeFalse();
    $this->get(route('home'))->assertInertia(fn ($page) => $page->has('partenaires', 2));

    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.logos-partenaires.visibilite', $logo));
    expect($logo->fresh()->visible)->toBeTrue();
});

test('a logo moves one place earlier or later in the band', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $genis = LogoPartenaire::where('nom', 'Genis Academic')->sole();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.logos-partenaires.ordre', $genis), ['direction' => 'avant'])
        ->assertRedirect();

    expect(LogoPartenaire::query()->ordonnes()->pluck('nom')->all())
        ->toBe(['Nova-Tech', 'Genis Academic', 'Nordex Consulting Group']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.logos-partenaires.ordre', LogoPartenaire::where('nom', 'Nova-Tech')->sole()), ['direction' => 'avant']);

    expect(LogoPartenaire::query()->ordonnes()->pluck('nom')->first())->toBe('Nova-Tech');
});

test('deleting an uploaded logo removes its file, never a brand image of the site', function () {
    Storage::fake('public');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    Storage::disk('public')->put('logos-partenaires/atlas.png', 'x');
    $televerse = LogoPartenaire::factory()->create(['logo' => '/storage/logos-partenaires/atlas.png']);
    $marque = LogoPartenaire::where('nom', 'Nova-Tech')->sole();

    $this->actingAs($promotrice, 'admin')->delete(route('espace-admin.logos-partenaires.destroy', $televerse))->assertRedirect();
    $this->actingAs($promotrice, 'admin')->delete(route('espace-admin.logos-partenaires.destroy', $marque))->assertRedirect();

    expect(LogoPartenaire::whereKey([$televerse->id, $marque->id])->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('logos-partenaires/atlas.png');
    expect(file_exists(public_path('images/brand/partners/nova-tech-logo.png')))->toBeTrue();
});

test('the logos page follows the rights on the entreprises', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $logo = LogoPartenaire::where('nom', 'Nova-Tech')->sole();

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.logos-partenaires.index'))
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin/logos-partenaires')
            ->has('logos', 3)
            ->where('actions.creer', false)
            ->where('actions.supprimer', false));

    $this->actingAs($juriste, 'admin')->patch(route('espace-admin.logos-partenaires.visibilite', $logo))->assertForbidden();
    $this->actingAs($juriste, 'admin')->delete(route('espace-admin.logos-partenaires.destroy', $logo))->assertForbidden();

    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $this->actingAs($marketing, 'admin')->delete(route('espace-admin.logos-partenaires.destroy', $logo))->assertForbidden();

    $sansDroit = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $this->actingAs($sansDroit, 'admin')->get(route('espace-admin.logos-partenaires.index'))->assertForbidden();

    expect($logo->fresh()->visible)->toBeTrue();
});
