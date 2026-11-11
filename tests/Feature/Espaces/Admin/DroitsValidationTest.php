<?php

use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Models\Contrat;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Support\DroitsMatrix;
use Illuminate\Support\Facades\Storage;

/**
 * A role the directrice set to "Gestion" (see, create, edit) on a domain:
 * it works on the records but cannot validate them.
 */
function membreEnGestion(Donnee $donnee): MembreEquipe
{
    DroitsMatrix::definir(MembreEquipeRole::CommunicationMarketing, $donnee, NiveauDroit::Gestion);

    return MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
}

test('validating a signed contract takes the validation right, editing rights are not enough', function () {
    $membre = membreEnGestion(Donnee::ContratsClauses);
    $contrat = Contrat::factory()->signe()->create();

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.contrats.show', $contrat))
        ->assertInertia(fn ($page) => $page->where('peutModifier', true)->where('peutValider', false));

    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF'])
        ->assertForbidden();

    expect($contrat->fresh()->statut)->toBe('SIGNE');

    $this->actingAs(MembreEquipe::factory()->juriste()->create(), 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF'])
        ->assertRedirect();

    expect($contrat->fresh()->statut)->toBe('ACTIF');
});

test('recording a manual payment, which confirms it at once, takes the validation right', function () {
    $membre = membreEnGestion(Donnee::FacturesPaiements);
    $facture = Facture::factory()->create(['montant' => 50000, 'montant_paye' => 0]);

    $this->actingAs($membre, 'admin')
        ->post(route('espace-admin.factures.paiements.store', $facture), ['montant' => 50000, 'moyen' => 'ESPECES'])
        ->assertForbidden();

    expect($facture->paiements()->count())->toBe(0)
        ->and((float) $facture->fresh()->montant_paye)->toBe(0.0);
});

test('a pending payment is confirmed only with the validation right, but can be marked failed with the editing right', function () {
    $membre = membreEnGestion(Donnee::FacturesPaiements);
    $facture = Facture::factory()->create(['montant' => 50000, 'montant_paye' => 0]);
    $nouveau = fn () => Paiement::create(['facture_id' => $facture->id, 'montant' => 50000, 'moyen' => 'OM', 'statut' => 'EN_ATTENTE']);

    $aConfirmer = $nouveau();
    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.paiements.update', $aConfirmer), ['statut' => 'CONFIRME'])
        ->assertForbidden();
    expect($aConfirmer->fresh()->statut)->toBe('EN_ATTENTE');

    $enEchec = $nouveau();
    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.paiements.update', $enEchec), ['statut' => 'ECHEC'])
        ->assertRedirect();
    expect($enEchec->fresh()->statut)->toBe('ECHEC');

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.factures.show', $facture))
        ->assertInertia(fn ($page) => $page->where('peutValider', false));
});

test('private files (identity documents, CVs, signatures) are never served by a public storage url', function () {
    Storage::disk('local')->put('inscriptions-pieces/essai/cni.pdf', 'contenu');

    $this->get('/storage/inscriptions-pieces/essai/cni.pdf')->assertNotFound();
    $this->put('/storage/inscriptions-pieces/essai/cni.pdf', ['fichier' => 'x'])->assertNotFound();

    Storage::disk('local')->delete('inscriptions-pieces/essai/cni.pdf');
});
