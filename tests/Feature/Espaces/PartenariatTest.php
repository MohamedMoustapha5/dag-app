<?php

use App\Enums\MembreEquipeRole;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\MembreEquipe;
use App\Models\Partenariat;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\PartenariatContratNotification;
use App\Notifications\PartenariatRefuseNotification;
use App\Notifications\PartenariatValideNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * The client signs its partnership contract as on the contract page.
 */
function signerContratPartenariat(Client $client, Contrat $contrat): TestResponse
{
    return test()->actingAs($client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Awa Ndiaye',
            'accepte' => '1',
            'forme_juridique' => 'Société à Responsabilité Limitée (SARL)',
            'niu' => 'p120018531521s',
            'qualite' => 'Gérante',
        ]);
}

function demandePartenariat(): array
{
    return [
        'domaines' => ['RECRUTEMENT', 'DIGITAL'],
        'message' => 'Batiplus recrute chaque année des profils digitaux et souhaite construire un partenariat durable avec DAG.',
    ];
}

test('an entreprise sees the partnership page with the request form', function () {
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->component('espaces/client/partenariat')
            ->has('domaines', 4)
            ->where('partenariat', null)
            ->where('peutDemander', true));
});

test('the partnership is reserved to entreprises and organisations', function () {
    $client = Client::factory()->create(['type' => 'PARTICULIER']);

    $this->actingAs($client->user, 'web')->get(route('espace-client.partenariat'))->assertForbidden();
    $this->actingAs($client->user, 'web')->post(route('espace-client.partenariat.demander'), demandePartenariat())->assertForbidden();

    expect(Partenariat::count())->toBe(0);
});

test('an organisation asks for a partnership and the team is alerted', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['type' => 'ORGANISATION', 'nom' => 'ONG Avenir']);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.partenariat.demander'), demandePartenariat())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $partenariat = Partenariat::sole();
    expect($partenariat->client_id)->toBe($client->id)
        ->and($partenariat->origine)->toBe('DEMANDE')
        ->and($partenariat->statut)->toBe('EN_ETUDE')
        ->and($partenariat->domaines)->toBe(['RECRUTEMENT', 'DIGITAL'])
        ->and($partenariat->reference)->toStartWith('DAG-PAR-');
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);
});

test('a partnership request needs at least one known domain and a presentation', function (array $saisie, string $champ) {
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.partenariat.demander'), [...demandePartenariat(), ...$saisie])
        ->assertSessionHasErrors($champ);

    expect(Partenariat::count())->toBe(0);
})->with([
    'no domain' => [['domaines' => []], 'domaines'],
    'unknown domain' => [['domaines' => ['IMMOBILIER']], 'domaines.0'],
    'no presentation' => [['message' => ''], 'message'],
    'presentation too short' => [['message' => 'Bonjour DAG'], 'message'],
]);

test('a structure cannot ask again while a partnership is under way', function (string $statut) {
    $client = Client::factory()->entreprise()->create();
    Partenariat::factory()->create(['client_id' => $client->id, 'statut' => $statut]);

    $this->actingAs($client->user, 'web')
        ->post(route('espace-client.partenariat.demander'), demandePartenariat())
        ->assertSessionHasErrors('domaines');

    expect(Partenariat::count())->toBe(1);
})->with(['EN_ETUDE', 'CONTRAT']);

test('accepting a request drafts the partnership contract and sends it to the structure', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create(['nom' => 'Batiplus SARL', 'secteur_activite' => 'BTP', 'ville' => 'Douala']);
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id, 'domaines' => ['RECRUTEMENT']]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 24])
        ->assertRedirect();

    $partenariat->refresh();
    $contrat = $partenariat->contrat;
    expect($partenariat->statut)->toBe('CONTRAT')
        ->and($partenariat->duree_mois)->toBe(24)
        ->and($partenariat->traite_par_id)->toBe($promotrice->id)
        ->and($partenariat->etape())->toBe('A_SIGNER');
    expect($contrat->type)->toBe('CONTRAT_PARTENAIRE')
        ->and($contrat->statut)->toBe('ENVOYE')
        ->and($contrat->client_id)->toBe($client->id)
        ->and($contrat->montant)->toBeNull()
        ->and($contrat->contenu)->toContain('Batiplus SARL (ci-après « Partenaire 2 »), [forme juridique, à compléter à la signature]')
        ->and($contrat->contenu)->toContain('immatriculée sous le numéro du code NIU [NIU, à compléter à la signature], dont le siège social est établi à Douala (Cameroun)')
        ->and($contrat->contenu)->toContain('dans les villes de Douala et Yaoundé,')
        ->and($contrat->contenu)->toContain('soumis aux juridictions compétentes de Douala,')
        ->and($contrat->contenu)->toContain('conclu pour une durée de 2 ans')
        ->and($contrat->contenu)->toContain('recrutement et stages des talents formés par DAG')
        ->and($contrat->contenu)->toContain('NIU P010618877302Q')
        ->and($contrat->contenu)->toContain($partenariat->reference)
        ->and($contrat->contenu)->not->toContain('{{');
    Notification::assertSentTo($client->user, PartenariatContratNotification::class);

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->where('partenariat.etape', 'A_SIGNER')
            ->where('partenariat.parcours.prochaine.action', 'signer_contrat')
            ->where('partenariat.contrat.id', $contrat->id)
            ->where('peutDemander', false));
});

test('a request is accepted only for a proposed duration and only once', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $partenariat = Partenariat::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 3])
        ->assertSessionHasErrors('duree_mois');
    expect($partenariat->fresh()->statut)->toBe('EN_ETUDE');

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12]);
    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12])
        ->assertConflict();

    expect(Contrat::count())->toBe(1);
});

test('refusing a request tells the structure why', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $partenariat = Partenariat::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.refuser', $partenariat), ['motif' => ''])
        ->assertSessionHasErrors('motif');

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.refuser', $partenariat), ['motif' => 'Votre activité ne correspond pas encore à nos programmes.'])
        ->assertRedirect();

    expect($partenariat->fresh()->statut)->toBe('REFUSE')
        ->and($partenariat->fresh()->motif_refus)->toBe('Votre activité ne correspond pas encore à nos programmes.');
    expect(Contrat::count())->toBe(0);
    Notification::assertSentTo($partenariat->client->user, PartenariatRefuseNotification::class);

    $this->actingAs($partenariat->client->user, 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->where('partenariat.etape', 'REFUSE')
            ->where('peutDemander', true));
});

test('DAG proposes a partnership to a structure that did not ask for it', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create(['nom' => 'Atlas Corp']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.proposer'), [
            'client_id' => $client->id,
            'duree_mois' => 6,
            'domaines' => ['FORMATION', 'EVENEMENTS'],
            'message' => 'Nous serions ravis de vous compter parmi nos partenaires.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $partenariat = Partenariat::sole();
    expect($partenariat->origine)->toBe('PROPOSITION')
        ->and($partenariat->etape())->toBe('A_SIGNER')
        ->and($partenariat->contrat->contenu)->toContain('une durée de 6 mois');
    Notification::assertSentTo($client->user, PartenariatContratNotification::class);
});

test('a partnership cannot be proposed to a particulier nor next to one under way', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $particulier = Client::factory()->create(['type' => 'PARTICULIER']);
    $partenaire = Client::factory()->entreprise()->create();
    Partenariat::factory()->actif()->create(['client_id' => $partenaire->id]);

    foreach ([$particulier, $partenaire] as $client) {
        $this->actingAs($promotrice, 'admin')
            ->post(route('espace-admin.partenariats.proposer'), ['client_id' => $client->id, 'duree_mois' => 12, 'domaines' => ['DIGITAL']])
            ->assertSessionHasErrors('client_id');
    }

    expect(Partenariat::count())->toBe(1);
});

test('a role without the validation right cannot handle partnerships', function () {
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $partenariat = Partenariat::factory()->create();
    $client = Client::factory()->entreprise()->create();

    $this->actingAs($membre, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12])->assertForbidden();
    $this->actingAs($membre, 'admin')->post(route('espace-admin.partenariats.refuser', $partenariat), ['motif' => 'Motif suffisamment long.'])->assertForbidden();
    $this->actingAs($membre, 'admin')->post(route('espace-admin.partenariats.proposer'), ['client_id' => $client->id, 'duree_mois' => 12, 'domaines' => ['DIGITAL']])->assertForbidden();

    expect($partenariat->fresh()->statut)->toBe('EN_ETUDE');
    expect(Contrat::count())->toBe(0);
});

test('the team page lists the partnerships with their step', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $partenariat = Partenariat::factory()->create();

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.partenariats.index'))
        ->assertInertia(fn ($page) => $page
            ->component('espaces/admin/partenariats')
            ->where('partenariats.0.id', $partenariat->id)
            ->where('partenariats.0.etape', 'EN_ETUDE')
            ->where('actions.gerer', false)
            ->where('clientsProposables', []));

    $sansDroit = MembreEquipe::factory()->create(['role' => MembreEquipeRole::ControleGestion]);
    $this->actingAs($sansDroit, 'admin')->get(route('espace-admin.partenariats.index'))->assertForbidden();
});

test('the structure signs, DAG validates, and the structure becomes an official partner', function () {
    Storage::fake('local');
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id]);
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12]);
    $contrat = $partenariat->fresh()->contrat;

    signerContratPartenariat($client, $contrat)->assertRedirect(route('espace-client.partenariat'));

    $signe = $contrat->fresh();
    expect($signe->statut)->toBe('SIGNE')
        ->and($signe->forme_juridique)->toBe('Société à Responsabilité Limitée (SARL)')
        ->and($signe->niu)->toBe('P120018531521S')
        ->and($signe->qualite_signataire)->toBe('Gérante')
        ->and($signe->contenu)->toContain('(ci-après « Partenaire 2 »), Société à Responsabilité Limitée (SARL) constituée')
        ->and($signe->contenu)->toContain('code NIU P120018531521S')
        ->and($signe->contenu)->toContain('Représentée aux fins des présentes par Awa Ndiaye, Gérante,')
        ->and($signe->contenu)->not->toContain('à compléter à la signature')
        ->and($partenariat->fresh()->etape())->toBe('A_VALIDER')
        ->and($client->estPartenaireOfficiel())->toBeFalse();
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class, fn ($notification) => $notification->evenement() === 'partenariat.signe');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF'])
        ->assertRedirect();

    $partenariat->refresh();
    expect($partenariat->statut)->toBe('ACTIF')
        ->and($partenariat->debut->toDateString())->toBe(today()->toDateString())
        ->and($partenariat->fin->toDateString())->toBe(today()->addMonthsNoOverflow(12)->subDay()->toDateString())
        ->and($client->estPartenaireOfficiel())->toBeTrue();
    Notification::assertSentTo($client->user, PartenariatValideNotification::class);

    $this->actingAs($client->user->fresh(), 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->where('client.estPartenaireOfficiel', true)
            ->where('partenariat.etape', 'ACTIF')
            ->where('peutDemander', false));
});

test('terminating the partnership contract ends the official partner status', function (string $statutContrat) {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'type' => 'CONTRAT_PARTENAIRE', 'statut' => 'ACTIF']);
    $partenariat = Partenariat::factory()->actif()->create(['client_id' => $client->id, 'contrat_id' => $contrat->id]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => $statutContrat])
        ->assertRedirect();

    expect($partenariat->fresh()->statut)->toBe('TERMINE')
        ->and($client->estPartenaireOfficiel())->toBeFalse();
})->with(['RESILIE', 'TERMINE']);

test('a partnership contract marked as refused cancels the partnership and lets the structure ask again', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'type' => 'CONTRAT_PARTENAIRE', 'statut' => 'ENVOYE']);
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id, 'statut' => 'CONTRAT', 'contrat_id' => $contrat->id, 'duree_mois' => 12]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'REFUSE'])
        ->assertRedirect();

    expect($partenariat->fresh()->statut)->toBe('ANNULE');
    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page->where('peutDemander', true));
});

test('a refused partnership contract taken up again by DAG brings the partnership back', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'type' => 'CONTRAT_PARTENAIRE', 'statut' => 'REFUSE']);
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id, 'statut' => 'ANNULE', 'contrat_id' => $contrat->id, 'duree_mois' => 12]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'BROUILLON'])
        ->assertRedirect();

    expect($partenariat->fresh()->statut)->toBe('CONTRAT')
        ->and($partenariat->fresh()->etape())->toBe('EN_REDACTION');
});

test('a signed partnership contract terminated before its validation cancels the partnership', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'type' => 'CONTRAT_PARTENAIRE', 'statut' => 'SIGNE']);
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id, 'statut' => 'CONTRAT', 'contrat_id' => $contrat->id, 'duree_mois' => 12]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'RESILIE'])
        ->assertRedirect();

    expect($partenariat->fresh()->statut)->toBe('ANNULE')
        ->and($client->estPartenaireOfficiel())->toBeFalse();
});

test('a partnership past its end date no longer makes an official partner', function () {
    $client = Client::factory()->entreprise()->create();
    Partenariat::factory()->actif()->create([
        'client_id' => $client->id,
        'debut' => today()->subYear()->toDateString(),
        'fin' => today()->subDay()->toDateString(),
    ]);

    expect($client->estPartenaireOfficiel())->toBeFalse();
    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->where('partenariat.etape', 'TERMINE')
            ->where('peutDemander', true));
});

test('the comptes page marks the official partners', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    Partenariat::factory()->actif()->create(['client_id' => $client->id]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.clients.index'))
        ->assertInertia(fn ($page) => $page
            ->where('clients.0.id', $client->id)
            ->where('clients.0.partenaireOfficiel', true));
});

test('a partnership contract asks the partner for its legal form, NIU and title when signing', function (string $champ) {
    Storage::fake('local');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $partenariat = Partenariat::factory()->create();
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12]);
    $contrat = $partenariat->fresh()->contrat;

    $this->actingAs($partenariat->client->user, 'web')
        ->get(route('espace-client.contrats.show', $contrat))
        ->assertInertia(fn ($page) => $page->where('societe.exigee', true));

    $this->actingAs($partenariat->client->user, 'web')
        ->post(route('espace-client.contrats.signer', $contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Awa Ndiaye',
            'accepte' => '1',
            'forme_juridique' => 'SARL',
            'niu' => 'P120018531521S',
            'qualite' => 'Gérante',
            $champ => '',
        ])
        ->assertSessionHasErrors($champ);

    expect($contrat->fresh()->statut)->toBe('ENVOYE');
})->with(['forme_juridique', 'niu', 'qualite']);

test('a NIU with spaces or symbols is refused', function () {
    Storage::fake('local');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $partenariat = Partenariat::factory()->create();
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12]);

    $this->actingAs($partenariat->client->user, 'web')
        ->post(route('espace-client.contrats.signer', $partenariat->fresh()->contrat), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            'nom' => 'Awa Ndiaye',
            'accepte' => '1',
            'forme_juridique' => 'SARL',
            'niu' => 'P12 <script>',
            'qualite' => 'Gérante',
        ])
        ->assertSessionHasErrors('niu');
});

test('a partner outside Douala adds its city to the operational seat and the competent courts', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create(['nom' => 'Novatech', 'ville' => 'Dschang']);
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id]);

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 12]);

    expect($partenariat->fresh()->contrat->contenu)
        ->toContain('dont le siège social est établi à Dschang (Cameroun)')
        ->toContain('dans les villes de Douala, Yaoundé et Dschang,')
        ->toContain('soumis aux juridictions compétentes de Douala ou de Dschang,');
});

test('an open-ended partnership is tacitly renewed and has no end date', function () {
    Storage::fake('local');
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->entreprise()->create();
    $partenariat = Partenariat::factory()->create(['client_id' => $client->id]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.partenariats.accepter', $partenariat), ['duree_mois' => 0])
        ->assertSessionHasNoErrors();

    $contrat = $partenariat->fresh()->contrat;
    expect($contrat->contenu)
        ->toContain('conclu pour une durée indéterminée')
        ->toContain('tacite reconduction');

    signerContratPartenariat($client, $contrat);
    $this->actingAs($promotrice, 'admin')->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ACTIF']);

    $partenariat->refresh();
    expect($partenariat->statut)->toBe('ACTIF')
        ->and($partenariat->fin)->toBeNull()
        ->and($partenariat->etape())->toBe('ACTIF')
        ->and($client->estPartenaireOfficiel())->toBeTrue();

    $this->actingAs($client->user->fresh(), 'web')
        ->get(route('espace-client.partenariat'))
        ->assertInertia(fn ($page) => $page
            ->where('partenariat.duree', 'Durée indéterminée')
            ->where('partenariat.fin', null)
            ->where('peutDemander', false));
});
