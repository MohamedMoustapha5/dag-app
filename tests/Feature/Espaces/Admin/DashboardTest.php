<?php

use App\Enums\MembreEquipeRole;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Models\Service;

test('a membre can see the dashboard with its kpis', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::Promotrice]);

    $serviceDag = Service::factory()->create(['fournisseur' => 'DAG']);
    $demande = Demande::factory()->create(['service_id' => $serviceDag->id, 'statut' => 'EN_ETUDE']);
    $facture = Facture::factory()->create(['demande_id' => $demande->id, 'montant' => 100000, 'montant_paye' => 100000]);
    Paiement::create([
        'facture_id' => $facture->id,
        'montant' => 100000,
        'moyen' => 'MTN_MOMO',
        'statut' => 'CONFIRME',
        'date_paiement' => now(),
    ]);

    $response = $this->actingAs($membre->user)->get(route('espace-admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-dashboard')
        ->where('kpis.chiffreAffairesMois', 100000)
        ->where('kpis.demandesEnAttente', 1)
        ->where('repartitionCa.total', 100000)
        ->has('paiements.7j')
        ->has('paiements.30j')
        ->has('paiements.3mois')
        ->has('servicesPlusDemandes', 1));
});

test('an unconfirmed paiement is excluded from the chiffre affaires', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::Promotrice]);

    $facture = Facture::factory()->create(['montant' => 75000, 'montant_paye' => 0]);
    Paiement::create([
        'facture_id' => $facture->id,
        'montant' => 75000,
        'moyen' => 'VIREMENT',
        'statut' => 'EN_ATTENTE',
        'date_paiement' => now(),
    ]);

    $response = $this->actingAs($membre->user)->get(route('espace-admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-dashboard')
        ->where('kpis.chiffreAffairesMois', 0));
});
