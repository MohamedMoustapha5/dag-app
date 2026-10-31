<?php

use App\Enums\MembreEquipeRole;
use App\Enums\UserType;
use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\Client;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

test('uuid-keyed models receive a valid uuid primary key on creation', function () {
    $client = Client::factory()->create();

    expect(Str::isUuid($client->id))->toBeTrue();
});

test('a user exposes its client profile through the matching relation', function () {
    $client = Client::factory()->create();

    expect($client->user->type)->toBe(UserType::Client);
    expect($client->user->client->is($client))->toBeTrue();
});

test('a membre equipe role casts to the MembreEquipeRole enum and exposes a french label', function () {
    $membre = MembreEquipe::factory()->promotrice()->create();

    expect($membre->role)->toBe(MembreEquipeRole::Promotrice);
    expect($membre->role->label())->toBe('Directrice');
    expect($membre->estActif())->toBeTrue();
});

test('a formation session reports the remaining places as inscriptions are validated', function () {
    $session = FormationSession::factory()->for(Formation::factory())->create(['places' => 2]);

    expect($session->placesRestantes())->toBe(2);

    $session->inscriptions()->create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => $session->formation_id,
        'statut' => 'VALIDEE',
    ]);

    expect($session->placesRestantes())->toBe(1);
});

test('an apprenant cannot submit two candidatures for the same offre', function () {
    $apprenant = Apprenant::factory()->create();
    $offre = OffreEmploi::factory()->create();

    Candidature::create(['apprenant_id' => $apprenant->id, 'offre_id' => $offre->id]);

    expect(fn () => Candidature::create(['apprenant_id' => $apprenant->id, 'offre_id' => $offre->id]))
        ->toThrow(QueryException::class);
});

test('a published offre scope excludes offres past their deadline', function () {
    OffreEmploi::factory()->create(['statut' => 'PUBLIEE', 'date_limite' => now()->addWeek()]);
    OffreEmploi::factory()->create(['statut' => 'PUBLIEE', 'date_limite' => now()->subWeek()]);
    OffreEmploi::factory()->cloturee()->create();

    expect(OffreEmploi::publiees()->count())->toBe(1);
});
