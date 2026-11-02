<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\MembreEquipe;

test('a controle de gestion can see the statistiques dashboard', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    Apprenant::factory()->count(3)->create();
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Facture::factory()->create(['montant' => 100000, 'montant_paye' => 100000, 'statut' => 'PAYEE']);
    Facture::factory()->create(['montant' => 50000, 'montant_paye' => 0, 'statut' => 'EN_ATTENTE']);

    $response = $this->actingAs($membre, 'admin')->get(route('espace-admin.statistiques'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin-statistiques')
        ->where('activite.apprenants', null)
        ->where('demandesParStatut.EN_ETUDE', 2)
        ->where('finances.factureEncaissee', 100000)
        ->where('finances.factureEnAttente', 50000)
        ->where('finances.facturesImpayeesCount', 1));
});

test('statistics only show the domains the viewer may see', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    Apprenant::factory()->count(2)->create();
    Demande::factory()->create(['statut' => 'EN_ETUDE']);
    Facture::factory()->create(['montant' => 100000, 'montant_paye' => 100000, 'statut' => 'PAYEE']);

    $this->actingAs($juriste, 'admin')->get(route('espace-admin.statistiques'))
        ->assertInertia(fn ($page) => $page
            ->where('activite.apprenants', 2)
            ->where('demandesParStatut.EN_ETUDE', 1)
            ->where('finances', null));
});

test('a role without access to statistiques cannot reach the page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.statistiques'))
        ->assertForbidden();
});
