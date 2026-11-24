<?php

use App\Enums\MembreEquipeRole;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\Formule;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Models\Partenariat;
use App\Notifications\ContratASignerNotification;
use App\Notifications\PartenariatContratNotification;
use Database\Seeders\ContratDagSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function contratPrestationGenere(Client $client, array $surcharges = []): array
{
    return [
        'type' => 'PRESTATION_SERVICE',
        'client_id' => $client->id,
        'objet' => 'Réalisation de 120 badges',
        'description' => 'Conception et impression de 120 badges pour le personnel.',
        'montant' => 60000,
        ...$surcharges,
    ];
}

test('a promotrice generates a prestation contract for an entreprise as a draft to review', function () {
    Notification::fake();
    $this->seed(ContratDagSeeder::class);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create(['nom' => 'Batiplus SARL']);

    $response = $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.generer'), contratPrestationGenere($client));

    $contrat = Contrat::sole();
    $response->assertRedirect(route('espace-admin.contrats.show', $contrat));

    $demande = $contrat->demande;
    expect($contrat->type)->toBe('PRESTATION_SERVICE')
        ->and($contrat->statut)->toBe('BROUILLON')
        ->and($contrat->client_id)->toBe($client->id)
        ->and($contrat->libelleObjet())->toBe('Prestation — Réalisation de 120 badges')
        ->and($contrat->contenu)
        ->toContain('Le présent contrat porte sur : Réalisation de 120 badges.')
        ->toContain('Conception et impression de 120 badges pour le personnel.')
        ->toContain('Le coût du service est fixé à 60 000 FCFA')
        ->toContain("Il se rapporte à la demande {$demande->reference}")
        ->not->toContain('{{');
    expect($demande->client_id)->toBe($client->id)
        ->and($demande->statut)->toBe('DEVIS_ENVOYE')
        ->and($demande->devis_valide_le)->not->toBeNull()
        ->and((float) $demande->montant_negocie)->toBe(60000.0);
    $facture = Facture::where('demande_id', $demande->id)->sole();
    expect((float) $facture->montant)->toBe(60000.0)->and($facture->statut)->toBe('EN_ATTENTE');
    expect($demande->paiementEstOuvert())->toBeFalse();
    Notification::assertNothingSent();
});

test('a generated contract sent at once is signed by the entreprise, validated by DAG, then paid online', function () {
    Notification::fake();
    Storage::fake('local');
    $this->seed(ContratDagSeeder::class);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.generer'), contratPrestationGenere($client, ['envoyer' => true]))
        ->assertRedirect();

    $contrat = Contrat::sole();
    expect($contrat->statut)->toBe('ENVOYE');
    Notification::assertSentTo($client->user, ContratASignerNotification::class);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Awa Ndiaye',
            'accepte' => true,
            'telephone' => '+237 690 00 00 00',
            'cni_numero' => '112233445',
            'cni_lieu_delivrance' => 'Douala',
            'cni_date_delivrance' => '2022-03-15',
            'ville_residence' => 'Douala',
            'quartier_prestation' => 'Akwa',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF'])
        ->assertRedirect();

    $demande = $contrat->demande->fresh();
    expect($demande->statut)->toBe('CONTRAT_SIGNE')
        ->and($demande->paiementEstOuvert())->toBeTrue();
    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.demandes.show', $demande))
        ->assertOk();
});

test('a subscription contract is generated from the chosen formule with the subscription text', function () {
    $this->seed(ContratDagSeeder::class);
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $formule = Formule::factory()->create(['nom' => 'Croissance']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.generer'), [
            'type' => 'ABONNEMENT',
            'client_id' => $client->id,
            'formule_id' => $formule->id,
            'description' => 'Gestion mensuelle des réseaux sociaux.',
            'montant' => 150000,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $contrat = Contrat::sole();
    expect($contrat->type)->toBe('ABONNEMENT')
        ->and($contrat->demande->formule_id)->toBe($formule->id)
        ->and($contrat->contenu)
        ->toContain("Le présent contrat d'abonnement à la formule Croissance")
        ->toContain("Le prix de l'abonnement est fixé à 150 000 FCFA");
});

test('a partnership contract generated from the contracts page goes through the partnership path', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.generer'), [
            'type' => 'CONTRAT_PARTENAIRE',
            'client_id' => $client->id,
            'duree_mois' => 12,
            'domaines' => ['DIGITAL'],
        ])
        ->assertSessionHasNoErrors();

    $partenariat = Partenariat::sole();
    expect($partenariat->origine)->toBe('PROPOSITION')
        ->and($partenariat->contrat->statut)->toBe('ENVOYE')
        ->and($partenariat->contrat->client_id)->toBe($client->id);
    expect(Facture::count())->toBe(0);
    Notification::assertSentTo($client->user, PartenariatContratNotification::class);
});

test('a contract is generated only for an entreprise or an organisation, with what was agreed', function (array $surcharges, string $champ) {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.contrats.generer'), contratPrestationGenere($client, $surcharges))
        ->assertSessionHasErrors($champ);

    expect(Contrat::count())->toBe(0)
        ->and(Demande::count())->toBe(0)
        ->and(Facture::count())->toBe(0);
})->with([
    'particulier' => [fn () => ['client_id' => Client::factory()->create(['type' => 'PARTICULIER'])->id], 'client_id'],
    'no entreprise' => [['client_id' => null], 'client_id'],
    'no subject' => [['objet' => ''], 'objet'],
    'no detail' => [['description' => ''], 'description'],
    'no amount' => [['montant' => ''], 'montant'],
    'unknown type' => [['type' => 'CONVENTION_STAGE'], 'type'],
    'modele of another type' => [fn () => ['modele_contrat_id' => ModeleContrat::create(['nom' => 'A', 'type' => 'ABONNEMENT', 'contenu' => 'x', 'statut' => 'ACTIF'])->id], 'modele_contrat_id'],
]);

test('generating a prestation contract needs the contracts and the factures rights', function (MembreEquipeRole $role) {
    $membre = MembreEquipe::factory()->create(['role' => $role]);
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($membre, 'admin')
        ->post(route('espace-admin.contrats.generer'), contratPrestationGenere($client))
        ->assertForbidden();

    expect(Contrat::count())->toBe(0)->and(Demande::count())->toBe(0);
})->with([
    'juriste: no factures right' => MembreEquipeRole::Juriste,
    'controle de gestion: contracts read only' => MembreEquipeRole::ControleGestion,
]);

test('the contracts page offers the generation to who may use it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $structure = Client::factory()->entreprise()->create(['nom' => 'Atlas Corp']);
    Client::factory()->create(['type' => 'PARTICULIER']);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertInertia(fn ($page) => $page
            ->has('generation.structures', 1)
            ->where('generation.structures.0.id', $structure->id)
            ->where('generation.peutFacturer', true)
            ->where('generation.peutPartenariat', true));

    $lecture = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $this->actingAs($lecture, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertInertia(fn ($page) => $page->where('generation', null));
});
