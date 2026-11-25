<?php

use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Formation;

test('demandes receive a sequential DAG-DEM reference on creation', function () {
    $client = Client::factory()->create();

    $first = Demande::create(['client_id' => $client->id, 'description' => 'Audit de page']);
    $second = Demande::create(['client_id' => $client->id, 'description' => 'Campagne Meta Ads']);

    expect($first->reference)->toBe('DAG-DEM-'.now()->year.'-0001');
    expect($second->reference)->toBe('DAG-DEM-'.now()->year.'-0002');
});

test('after a deletion the next reference follows the highest one, never reusing a taken number', function () {
    $client = Client::factory()->create();
    $annee = now()->year;

    $premiere = Demande::create(['client_id' => $client->id, 'description' => 'Audit de page']);
    Demande::create(['client_id' => $client->id, 'description' => 'Campagne Meta Ads']);
    $premiere->delete();

    $suivante = Demande::create(['client_id' => $client->id, 'description' => 'Site vitrine']);

    expect($suivante->reference)->toBe("DAG-DEM-{$annee}-0003");
});

test('numbering keeps going past 9999', function () {
    $client = Client::factory()->create();
    $annee = now()->year;
    Demande::create(['client_id' => $client->id, 'description' => 'A', 'reference' => "DAG-DEM-{$annee}-9999"]);
    Demande::create(['client_id' => $client->id, 'description' => 'B', 'reference' => "DAG-DEM-{$annee}-10000"]);

    expect(Demande::create(['client_id' => $client->id, 'description' => 'C'])->reference)->toBe("DAG-DEM-{$annee}-10001");
});

test('an explicit reference is not overwritten', function () {
    $client = Client::factory()->create();

    $demande = Demande::create([
        'client_id' => $client->id,
        'description' => 'Audit de page',
        'reference' => 'DAG-DEM-CUSTOM',
    ]);

    expect($demande->reference)->toBe('DAG-DEM-CUSTOM');
});

test('contrats receive a reference and a verification code on creation', function () {
    $contrat = Contrat::create([
        'type' => 'PRESTATION_SERVICE',
        'montant' => 85000,
    ]);

    expect($contrat->reference)->toStartWith('DAG-CTR-'.now()->year.'-');
    expect($contrat->code_verification)->toMatch('/^[A-Z0-9]{3}-\d{3}[A-Z0-9]$/');
});

test('factures and attestations each use their own reference prefix', function () {
    $client = Client::factory()->create();
    $facture = Facture::create(['client_id' => $client->id, 'montant' => 45000]);

    $formation = Formation::create([
        'nom' => 'Community Management',
        'description' => 'desc',
        'statut' => 'PUBLIEE',
    ]);
    $attestation = Attestation::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => $formation->id,
        'date_emission' => now(),
        'fichier_pdf' => 'attestations/demo.pdf',
    ]);

    expect($facture->reference)->toStartWith('DAG-F-'.now()->year.'-');
    expect($attestation->reference)->toStartWith('DAG-ATT-'.now()->year.'-');
});
