<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use App\Support\FacturePdf;

function factureRendue(Facture $facture): string
{
    return view('pdf.facture', FacturePdf::donnees($facture))->render();
}

test('the facture shows the client, the readable payment method, the payment reference and the Douala date', function () {
    $client = Client::factory()->create(['type' => 'ENTREPRISE', 'nom' => 'Batiplus SARL']);
    $facture = Facture::factory()->payee()->create([
        'client_id' => $client->id,
        'montant' => 10000,
        'moyen_paiement' => 'OM',
    ]);
    Paiement::create([
        'facture_id' => $facture->id,
        'montant' => 10000,
        'moyen' => 'OM',
        'statut' => 'CONFIRME',
        'reference_transaction' => 'OM-20261111-XYZ',
        'date_paiement' => '2026-11-11 23:39:00',
    ]);

    $html = factureRendue($facture);

    expect($html)
        ->toContain($facture->reference)
        ->toContain('Batiplus SARL')
        ->toContain('Entreprise')
        ->toContain($client->user->email)
        ->toContain('Orange Money')
        ->toContain('OM-20261111-XYZ')
        ->toContain('12 novembre 2026 à 00:39 (UTC+1)')
        ->toContain('10 000 FCFA')
        ->toContain('contact@agencydag.com');
});

test('RCCM, NIU and the VAT mention appear only once they are configured', function () {
    $facture = Facture::factory()->payee()->create();

    expect(factureRendue($facture))->not->toContain('RCCM :')->not->toContain('NIU :');

    config([
        'entreprise.rccm' => 'CM-DLA-2026-B-00001',
        'entreprise.niu' => 'M012345678901A',
        'entreprise.mention_tva' => 'Prix nets, sans TVA.',
    ]);

    expect(factureRendue($facture))
        ->toContain('RCCM : CM-DLA-2026-B-00001')
        ->toContain('NIU : M012345678901A')
        ->toContain('Prix nets, sans TVA.');
});
