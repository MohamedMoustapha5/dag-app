<?php

use App\Enums\MembreEquipeRole;
use App\Models\AccesRecrutement;
use App\Models\Client;
use App\Models\Facture;
use App\Models\ForfaitRecrutement;
use App\Models\MembreEquipe;

test('a membre with read access sees the forfaits and every recruitment access with its state', function () {
    $membre = MembreEquipe::factory()->juriste()->create();
    $enCours = AccesRecrutement::factory()->create([
        'client_id' => Client::factory()->entreprise()->create(['nom' => 'Batiplus SARL'])->id,
        'created_at' => now()->subMinutes(3),
    ]);
    $expire = AccesRecrutement::factory()->expire()->create(['created_at' => now()->subMinutes(2)]);
    $aVenir = AccesRecrutement::factory()->create([
        'debut' => now()->addMonth()->toDateString(),
        'fin' => now()->addMonths(2)->toDateString(),
        'created_at' => now()->subMinute(),
    ]);
    $enAttente = AccesRecrutement::factory()->create(['statut' => 'EN_ATTENTE_PAIEMENT', 'debut' => null, 'fin' => null]);
    AccesRecrutement::factory()->create(['statut' => 'ANNULE']);

    $response = $this->actingAs($membre, 'admin')->get(route('espace-admin.recrutement.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin/recrutement')
        ->has('forfaits', 3)
        ->where('forfaits.0.prix', '10000.00')
        ->has('acces', 4)
        ->where('acces.0.id', $enAttente->id)
        ->where('acces.0.etat', 'EN_ATTENTE')
        ->where('acces.1.id', $aVenir->id)
        ->where('acces.1.etat', 'A_VENIR')
        ->where('acces.2.id', $expire->id)
        ->where('acces.2.etat', 'EXPIRE')
        ->where('acces.3.id', $enCours->id)
        ->where('acces.3.etat', 'EN_COURS')
        ->where('acces.3.clientNom', 'Batiplus SARL')
        ->where('peutModifier', false));
});

test('a role without access to the entreprises cannot open the subscriptions page', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);

    $this->actingAs($membre, 'admin')
        ->get(route('espace-admin.recrutement.index'))
        ->assertForbidden();
});

test('a promotrice changes a forfait price, which applies to the next subscriptions only', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $forfait = ForfaitRecrutement::where('code', 'MENSUEL')->sole();
    $client = Client::factory()->entreprise()->create();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => $forfait->id]);
    $factureEmise = Facture::where('client_id', $client->id)->sole();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.recrutement.forfaits.update', $forfait), ['libelle' => 'Mensuel', 'prix' => 12500])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($forfait->fresh()->libelle)->toBe('Mensuel')
        ->and((float) $forfait->fresh()->prix)->toBe(12500.0)
        ->and((float) $factureEmise->fresh()->montant)->toBe(10000.0);

    $autreClient = Client::factory()->entreprise()->create();
    $this->actingAs($autreClient->user, 'web')
        ->post(route('espace-client.recrutement.souscrire'), ['forfait_id' => $forfait->id]);
    expect((float) Facture::where('client_id', $autreClient->id)->sole()->montant)->toBe(12500.0);
});

test('a forfait price must be a positive amount', function (mixed $prix) {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $forfait = ForfaitRecrutement::where('code', 'MENSUEL')->sole();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.recrutement.forfaits.update', $forfait), ['libelle' => '1 mois', 'prix' => $prix])
        ->assertSessionHasErrors('prix');

    expect((float) $forfait->fresh()->prix)->toBe(10000.0);
})->with([null, 'abc', 0, -5000]);

test('a role without the validation right cannot change a forfait price', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $forfait = ForfaitRecrutement::where('code', 'MENSUEL')->sole();

    $this->actingAs($membre, 'admin')
        ->patch(route('espace-admin.recrutement.forfaits.update', $forfait), ['libelle' => '1 mois', 'prix' => 1000])
        ->assertForbidden();

    expect((float) $forfait->fresh()->prix)->toBe(10000.0);
});
