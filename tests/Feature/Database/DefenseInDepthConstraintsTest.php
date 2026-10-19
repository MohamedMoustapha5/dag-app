<?php

use App\Models\Client;
use App\Models\EntreprisePartenaire;
use App\Models\Formule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('deleting a client preserves its abonnements instead of destroying the billing history', function () {
    $client = Client::factory()->create();
    $formule = Formule::factory()->create();

    $abonnementId = (string) Str::uuid();

    DB::table('abonnements')->insert([
        'id' => $abonnementId,
        'client_id' => $client->id,
        'formule_id' => $formule->id,
        'montant_negocie' => 50000,
        'date_souscription' => now()->toDateString(),
        'statut' => 'ACTIF',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $client->delete();

    $abonnement = DB::table('abonnements')->where('id', $abonnementId)->first();

    expect($abonnement)->not->toBeNull();
    expect($abonnement->client_id)->toBeNull();
});

test('entreprises partenaires cannot share an rccm at the database level', function () {
    EntreprisePartenaire::factory()->create(['rccm' => 'RC/DUP/001']);

    expect(fn () => EntreprisePartenaire::factory()->create(['rccm' => 'RC/DUP/001']))
        ->toThrow(QueryException::class);
});

test('entreprises partenaires cannot share an email at the database level', function () {
    EntreprisePartenaire::factory()->create(['email' => 'contact@partenaire.cd']);

    expect(fn () => EntreprisePartenaire::factory()->create(['email' => 'contact@partenaire.cd']))
        ->toThrow(QueryException::class);
});
