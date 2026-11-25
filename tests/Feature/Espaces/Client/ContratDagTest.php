<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Formule;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Notifications\AlerteEquipeNotification;
use App\Support\ContratPdf;
use App\Support\ContratRedaction;
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
    $client = Client::factory()->entreprise()->create(['nom' => 'Aïcha Ngono', 'telephone' => '690000000', 'ville' => 'Douala']);
    $demande = Demande::factory()->create([
        'client_id' => $client->id,
        'statut' => 'EN_ETUDE',
        'description' => 'Réalisation de 120 badges pour la société Kiro.',
    ]);

    test()->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 60000])
        ->assertRedirect();

    // The entreprise validates the devis: its contract is drafted and sent.
    test()->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
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
        'Objet de la demande', "Identification de l'intéressé", 'Coût du service', 'Délai de prestation',
        'Rectification du contrat', 'Annulation', 'Confidentialité', 'Réservation', 'Signature et preuve',
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
        ->toContain('4. Délai de prestation')
        ->toContain('remboursé des trois quarts (3/4)')
        ->toContain('8. Réservation')
        ->toContain("Base légale – Objet / nature du contrat : contrat de louage d'ouvrage")
        ->toContain('représentée par NGOU MANDOU AFSA, CEO')
        ->not->toContain('{{');

    $titres = collect(ContratPdf::sections($contrat->contenu))->pluck('titre')->filter()->values()->all();
    expect($titres)->toHaveCount(9);
});

test('every article of the DAG contracts carries the legal basis given by the juriste, except the subscription period', function () {
    foreach (ContratDagSeeder::articles() as $article) {
        expect($article['contenu'])->toContain('Base légale –');
    }

    $abonnement = collect(ContratPdf::sections(ContratDagSeeder::modeleAbonnement()))->filter(fn ($section) => $section['titre'] !== null);
    expect($abonnement)->toHaveCount(10);
    $abonnement
        ->reject(fn ($section) => $section['titre'] === '4. Durée et renouvellement')
        ->each(fn ($section) => expect(implode("\n", $section['paragraphes']))->toContain('Base légale –'));
});

test('a subscription gets the subscription contract, without the articles of a one-off prestation', function () {
    $this->seed(ContratDagSeeder::class);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create(['nom' => 'Aïcha Ngono']);
    $demande = Demande::factory()->create([
        'client_id' => $client->id,
        'formule_id' => Formule::factory()->create(['nom' => 'Croissance'])->id,
        'statut' => 'EN_ETUDE',
        'description' => 'Gestion mensuelle de nos réseaux sociaux.',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 150000])
        ->assertRedirect();
    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
        ->assertRedirect();

    $contrat = Contrat::where('demande_id', $demande->id)->sole();
    expect($contrat->type)->toBe('ABONNEMENT')
        ->and($contrat->contenu)
        ->toContain("Le présent contrat d'abonnement à la formule Croissance")
        ->toContain("3. Prix de l'abonnement\nLe prix de l'abonnement est fixé à 150 000 FCFA pour la période d'abonnement")
        ->toContain('4. Durée et renouvellement')
        ->toContain('Gestion mensuelle de nos réseaux sociaux.')
        ->toContain('une fois les prestations de la période démarrées, la période en cours reste due')
        ->toContain("5. Démarrage et exécution des prestations\n")
        ->not->toContain(". Délai de prestation\n")
        ->not->toContain('le jour de la livraison')
        ->not->toContain('{{');
    expect(collect(ContratPdf::sections($contrat->contenu))->pluck('titre')->filter())->toHaveCount(10);
});

test('a modele without its own articles still gets the clause library', function () {
    ModeleContrat::create(['nom' => 'Court', 'type' => 'PRESTATION_SERVICE', 'statut' => 'ACTIF', 'contenu' => 'Contrat entre DAG et {{client}}.']);
    Clause::create(['titre' => 'Objet', 'contenu' => 'Le contrat porte sur {{service}}.', 'ordre' => 0]);
    $demande = Demande::factory()->create(['statut' => 'EN_ETUDE']);

    $contenu = ContratRedaction::pour($demande, ModeleContrat::where('nom', 'Court')->sole());

    expect($contenu)->toContain("1. Objet\nLe contrat porte sur {$demande->libelleObjet()}.");
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

    // Out of the client's sight until the new devis is validated.
    expect($contrat->fresh()->statut)->toBe('BROUILLON');

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.demandes.devis.valider', $demande))
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
