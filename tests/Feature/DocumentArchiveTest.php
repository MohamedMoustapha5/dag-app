<?php

use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\MembreEquipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a facture PDF is built and stored privately the moment it is paid, before any download', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 80000, 'montant_paye' => 0]);

    expect($facture->fresh()->fichier_pdf)->toBeNull();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.factures.paiements.store', $facture), ['montant' => 80000, 'moyen' => 'OM'])
        ->assertSessionHasNoErrors();

    $chemin = "documents/factures/{$facture->id}.pdf";
    expect($facture->fresh()->fichier_pdf)->toBe($chemin);
    expect(Storage::disk('local')->get($chemin))->toStartWith('%PDF');
});

test('an unpaid or partly paid facture has no PDF yet', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $facture = Facture::factory()->create(['montant' => 80000, 'montant_paye' => 0]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.factures.paiements.store', $facture), ['montant' => 30000, 'moyen' => 'OM']);

    expect($facture->fresh()->fichier_pdf)->toBeNull();
    Storage::disk('local')->assertMissing("documents/factures/{$facture->id}.pdf");
});

test('downloading serves the stored file and never renders it again', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->payee()->create(['client_id' => $client->id]);
    $chemin = $facture->fresh()->fichier_pdf;

    expect($chemin)->toBe("documents/factures/{$facture->id}.pdf");

    Storage::disk('local')->put($chemin, 'PDF ARCHIVE');

    $reponse = $this->actingAs($client->user)
        ->get(route('espace-client.factures.pdf', $facture))
        ->assertOk()
        ->assertContent('PDF ARCHIVE');

    expect($reponse->headers->get('content-type'))->toBe('application/pdf');
    expect($reponse->headers->get('content-disposition'))->toContain("facture-{$facture->reference}.pdf");
});

test('a paid facture whose amounts change is rebuilt at once', function () {
    $facture = Facture::factory()->payee()->create(['montant' => 50000]);
    $chemin = $facture->fresh()->fichier_pdf;
    Storage::disk('local')->put($chemin, 'ANCIENNE VERSION');

    $facture->fresh()->update(['montant' => 60000, 'montant_paye' => 60000]);

    expect($facture->fresh()->fichier_pdf)->toBe($chemin);
    expect(Storage::disk('local')->get($chemin))->toStartWith('%PDF');
});

test('the signed contrat is built at the moment of signature and that file is what gets downloaded', function () {
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE', 'contenu' => "1. Objet\nTexte."]);

    $this->actingAs($client->user)
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Aïcha Ngono',
            'accepte' => true,
        ])
        ->assertRedirect();

    $chemin = "documents/contrats/{$contrat->id}.pdf";
    expect($contrat->fresh()->fichier_pdf)->toBe($chemin);
    Storage::disk('local')->assertExists($chemin);

    Storage::disk('local')->put($chemin, 'CONTRAT SIGNE ARCHIVE');

    $this->actingAs($client->user)
        ->get(route('espace-client.contrats.pdf', $contrat))
        ->assertOk()
        ->assertContent('CONTRAT SIGNE ARCHIVE');
});

test('an attestation PDF is built as soon as it is validated and served to the apprenant from storage', function () {
    $apprenant = Apprenant::factory()->create();
    $attestation = Attestation::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => Formation::factory()->create()->id,
        'date_emission' => now()->toDateString(),
    ]);

    $chemin = "documents/attestations/{$attestation->id}.pdf";
    expect($attestation->fresh()->fichier_pdf)->toBe($chemin);
    Storage::disk('local')->assertExists($chemin);

    Storage::disk('local')->put($chemin, 'ATTESTATION ARCHIVEE');

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.attestations.pdf', $attestation))
        ->assertContent('ATTESTATION ARCHIVEE');
});

test('a stored document whose file went missing is rebuilt on download', function () {
    $client = Client::factory()->create();
    $facture = Facture::factory()->payee()->create(['client_id' => $client->id]);
    Storage::disk('local')->delete($facture->fresh()->fichier_pdf);

    $reponse = $this->actingAs($client->user)->get(route('espace-client.factures.pdf', $facture))->assertOk();

    expect($reponse->getContent())->toStartWith('%PDF');
    Storage::disk('local')->assertExists("documents/factures/{$facture->id}.pdf");
});

test('the archiver command stores the PDF of every downloadable document still missing one', function () {
    $payee = Facture::factory()->payee()->create();
    Facture::query()->whereKey($payee->id)->toBase()->update(['fichier_pdf' => null]);
    Storage::disk('local')->delete("documents/factures/{$payee->id}.pdf");
    $impayee = Facture::factory()->create(['montant' => 80000, 'montant_paye' => 0]);

    $this->artisan('documents:archiver')->assertSuccessful();

    expect($payee->fresh()->fichier_pdf)->toBe("documents/factures/{$payee->id}.pdf");
    expect(Storage::disk('local')->get("documents/factures/{$payee->id}.pdf"))->toStartWith('%PDF');
    expect($impayee->fresh()->fichier_pdf)->toBeNull();
});
