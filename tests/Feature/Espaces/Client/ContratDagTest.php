<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Notifications\AlerteEquipeNotification;
use App\Support\ContratPdf;
use Database\Seeders\ContratDagSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * A client demande whose devis was sent with the DAG contract.
 *
 * @return array{0: Client, 1: Demande, 2: Contrat}
 */
function demandeAvecContratDag(): array
{
    test()->seed(ContratDagSeeder::class);
    $client = Client::factory()->create(['nom' => 'Aïcha Ngono', 'telephone' => '690000000', 'ville' => 'Douala']);
    $demande = Demande::factory()->create([
        'client_id' => $client->id,
        'statut' => 'EN_ETUDE',
        'description' => 'Réalisation de 120 badges pour la société Kiro.',
    ]);

    test()->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 60000])
        ->assertRedirect();

    return [$client, $demande, Contrat::where('demande_id', $demande->id)->sole()];
}

/**
 * @return array<string, mixed>
 */
function signatureAvecIdentite(array $surcharges = []): array
{
    return [
        'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
        'nom' => 'Aïcha Ngono',
        'accepte' => true,
        'telephone' => '+237 690 00 00 00',
        'cni_numero' => '112233445',
        'cni_lieu_delivrance' => 'Douala',
        'cni_date_delivrance' => '2022-03-15',
        'ville_residence' => 'Douala',
        'quartier_prestation' => 'Bonapriso',
        ...$surcharges,
    ];
}

test('the DAG contract seeder installs the juriste articles and active modeles, and can run again', function () {
    $ancien = ModeleContrat::create(['nom' => 'Ancien', 'type' => 'PRESTATION_SERVICE', 'contenu' => 'x', 'statut' => 'ACTIF']);

    $this->seed(ContratDagSeeder::class);
    $this->seed(ContratDagSeeder::class);

    expect(Clause::orderBy('ordre')->pluck('titre')->all())->toBe([
        'Objet de la demande', 'Coût du service', 'Délai de prestation', 'Rectification du contrat',
        'Annulation', 'Confidentialité', 'Réservation',
    ]);
    expect($ancien->fresh()->statut)->toBe('BROUILLON');
    expect(ModeleContrat::where('statut', 'ACTIF')->pluck('type')->sort()->values()->all())->toBe(['ABONNEMENT', 'PRESTATION_SERVICE']);
});

test('the contract sent with the devis follows the DAG contract, filled with the demande', function () {
    [, $demande, $contrat] = demandeAvecContratDag();

    expect($contrat->statut)->toBe('ENVOYE');
    expect($contrat->contenu)
        ->toContain('Aïcha Ngono')
        ->toContain($demande->reference)
        ->toContain("1. Objet de la demande\nLe présent contrat porte sur : {$demande->libelleObjet()}.")
        ->toContain('Réalisation de 120 badges pour la société Kiro.')
        ->toContain('Le coût du service est fixé à 60 000 FCFA')
        ->toContain('3. Délai de prestation')
        ->toContain('remboursé des trois quarts (3/4)')
        ->toContain('7. Réservation')
        ->not->toContain('{{');

    $titres = collect(ContratPdf::sections($contrat->contenu))->pluck('titre')->filter()->values()->all();
    expect($titres)->toHaveCount(7);
});

test('signing a service contract requires the identity the DAG contract asks for, and keeps it', function () {
    Storage::fake('local');
    [$client, $demande, $contrat] = demandeAvecContratDag();

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.contrats.show', $contrat))
        ->assertInertia(fn ($page) => $page
            ->where('identite.exigee', true)
            ->where('identite.telephone', '690000000')
            ->where('identite.villeResidence', 'Douala')
            ->where('rectification.restantes', 2));

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), signatureAvecIdentite([
            'cni_numero' => '',
            'quartier_prestation' => '',
            'cni_date_delivrance' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['cni_numero', 'quartier_prestation', 'cni_date_delivrance']);

    expect($contrat->fresh()->statut)->toBe('ENVOYE');

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), signatureAvecIdentite())
        ->assertRedirect(route('espace-client.demandes.show', $demande));

    $contrat->refresh();
    expect($contrat->statut)->toBe('SIGNE');
    expect($contrat->cni_numero)->toBe('112233445');
    expect($contrat->cni_lieu_delivrance)->toBe('Douala');
    expect($contrat->cni_date_delivrance->toDateString())->toBe('2022-03-15');
    expect($contrat->quartier_prestation)->toBe('Bonapriso');
    expect($contrat->telephone_cocontractant)->toBe('+237 690 00 00 00');

    expect(ContratPdf::generer($contrat))->toStartWith('%PDF');
});

test('a contract that is not for a demande is still signed without identity fields', function () {
    Storage::fake('local');
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE']);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png'),
            'nom' => 'Aïcha',
            'accepte' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($contrat->fresh()->statut)->toBe('SIGNE');
});

test('the client can ask for two rectifications, then a new contract is needed', function () {
    Notification::fake();
    [$client, , $contrat] = demandeAvecContratDag();
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.rectification', $contrat), ['motif' => ''])
        ->assertSessionHasErrors('motif');

    foreach ([1, 2] as $demandeNumero) {
        $this->actingAs($client->user, 'web')
            ->post(route('espace-client.contrats.rectification', $contrat), ['motif' => 'Le nombre de badges est de 150, pas 120.'])
            ->assertRedirect();

        $contrat->refresh();
        expect($contrat->statut)->toBe('BROUILLON');
        expect($contrat->nombre_rectifications)->toBe($demandeNumero);
        expect($contrat->motif_rectification)->toBe('Le nombre de badges est de 150, pas 120.');

        $this->actingAs($promotrice, 'admin')
            ->patch(route('espace-admin.contrats.contenu', $contrat), ['contenu' => "Texte corrigé {$demandeNumero}.\n\n1. Objet\n150 badges."])
            ->assertSessionHasNoErrors();
        $this->actingAs($promotrice, 'admin')
            ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ENVOYE'])
            ->assertSessionHasNoErrors();
    }

    expect($contrat->fresh()->contenu)->toStartWith('Texte corrigé 2.');
    Notification::assertSentTo(
        $promotrice,
        AlerteEquipeNotification::class,
        fn (AlerteEquipeNotification $alerte) => $alerte->evenement() === 'contrat.rectification',
    );

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.contrats.show', $contrat))
        ->assertInertia(fn ($page) => $page
            ->where('rectification.restantes', 0)
            ->where('rectification.peutDemander', false));

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.rectification', $contrat), ['motif' => 'Encore une modification à faire.'])
        ->assertStatus(409);

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
});

test('a new devis is a new contract, with its rectification allowance restored', function () {
    [$client, $demande, $contrat] = demandeAvecContratDag();
    $contrat->update(['nombre_rectifications' => 2, 'motif_rectification' => 'Ancien motif']);

    $this->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 75000])
        ->assertRedirect();

    $contrat->refresh();
    expect($contrat->nombre_rectifications)->toBe(0);
    expect($contrat->motif_rectification)->toBeNull();
    expect($contrat->contenu)->toContain('75 000 FCFA');
    expect($contrat->statut)->toBe('ENVOYE');
});

test('only a draft contract text can be edited, by a member allowed to', function () {
    [, , $contrat] = demandeAvecContratDag();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $lecture = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.contenu', $contrat), ['contenu' => 'Autre texte'])
        ->assertStatus(409);

    $contrat->update(['statut' => 'BROUILLON']);

    $this->actingAs($lecture, 'admin')
        ->patch(route('espace-admin.contrats.contenu', $contrat), ['contenu' => 'Autre texte'])
        ->assertForbidden();

    expect($contrat->fresh()->contenu)->not->toBe('Autre texte');
});
