<?php

use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\Facture;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Support\Parcours\ParcoursDemande;
use App\Support\Parcours\ParcoursInscription;

/**
 * The key of the step in progress and who acts on it, for one audience.
 *
 * @param  array{etapes: list<array{cle: string, etat: string}>, prochaine: array{acteur: string, aVous: bool}|null}  $parcours
 * @return array{0: string|null, 1: string|null, 2: bool|null}
 */
function etapeEnCours(array $parcours): array
{
    $enCours = collect($parcours['etapes'])->firstWhere('etat', 'en_cours');

    return [$enCours['cle'] ?? null, $parcours['prochaine']['acteur'] ?? null, $parcours['prochaine']['aVous'] ?? null];
}

test('each stage of a demande tells who acts next, for the client and for the team', function (string $statut, ?string $contrat, ?string $facture, string $etape, string $acteur) {
    $demande = Demande::factory()->create(['statut' => $statut, 'montant_negocie' => 50000]);

    if ($contrat !== null) {
        Contrat::factory()->create([
            'demande_id' => $demande->id,
            'statut' => $contrat,
            'date_signature' => in_array($contrat, ['SIGNE', 'ACTIF'], true) ? now() : null,
        ]);
    }

    if ($facture !== null) {
        Facture::factory()->create(['demande_id' => $demande->id, 'montant' => 50000, 'statut' => $facture, 'montant_paye' => $facture === 'PAYEE' ? 50000 : 0]);
    }

    $pourClient = ParcoursDemande::pour($demande->fresh(), 'client');
    $pourAdmin = ParcoursDemande::pour($demande->fresh(), 'admin');

    expect(etapeEnCours($pourClient))->toBe([$etape, $acteur, $acteur === 'client']);
    expect(etapeEnCours($pourAdmin))->toBe([$etape, $acteur, $acteur === 'dag']);
})->with([
    'just sent' => ['EN_ETUDE', null, null, 'devis', 'dag'],
    'devis and contract sent' => ['DEVIS_ENVOYE', 'ENVOYE', 'EN_ATTENTE', 'signature', 'client'],
    'rectification requested' => ['DEVIS_ENVOYE', 'BROUILLON', 'EN_ATTENTE', 'signature', 'dag'],
    'signed, to validate' => ['CONTRAT_SIGNE', 'SIGNE', 'EN_ATTENTE', 'validation', 'dag'],
    'validated, to pay' => ['CONTRAT_SIGNE', 'ACTIF', 'EN_ATTENTE', 'paiement', 'client'],
    'paid' => ['PAYEE', 'ACTIF', 'PAYEE', 'prestation', 'dag'],
    'delivered' => ['LIVREE', 'ACTIF', 'PAYEE', 'cloture', 'dag'],
]);

test('a finished demande has no next step, and a refused one is interrupted', function () {
    $terminee = Demande::factory()->create(['statut' => 'TERMINEE']);
    Contrat::factory()->valide()->create(['demande_id' => $terminee->id]);
    Facture::factory()->payee()->create(['demande_id' => $terminee->id]);

    $parcours = ParcoursDemande::pour($terminee->fresh(), 'client');
    expect($parcours['termine'])->toBeTrue();
    expect($parcours['prochaine'])->toBeNull();
    expect(collect($parcours['etapes'])->pluck('etat')->unique()->values()->all())->toBe(['faite']);

    $refusee = ParcoursDemande::pour(Demande::factory()->create(['statut' => 'REFUSEE']), 'admin');
    expect($refusee['interrompu'])->not->toBeNull();
    expect($refusee['prochaine'])->toBeNull();
});

test('each stage of an enrolment tells who acts next', function (string $statut, ?string $etape, ?string $acteur) {
    $inscription = Inscription::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => ($session = FormationSession::factory()->create())->formation_id,
        'formation_session_id' => $session->id,
        'montant_negocie' => 40000,
        'statut' => $statut,
    ]);

    if ($statut === 'EN_ATTENTE_PAIEMENT') {
        // Validating the dossier bills the fee: the facture is still unpaid.
        Facture::factory()->create(['client_id' => null, 'apprenant_id' => $inscription->apprenant_id, 'inscription_id' => $inscription->id]);
    }

    expect(etapeEnCours(ParcoursInscription::pour($inscription, 'client'))[0])->toBe($etape);
    expect(etapeEnCours(ParcoursInscription::pour($inscription, 'admin'))[1])->toBe($acteur);
})->with([
    'dossier under review' => ['DOSSIER_EN_ETUDE', 'examen', 'dag'],
    'dossier to complete' => ['DOSSIER_A_COMPLETER', 'dossier', 'client'],
    'fee to pay' => ['EN_ATTENTE_PAIEMENT', 'paiement', 'client'],
    'seat confirmed' => ['VALIDEE', 'formation', 'dag'],
    'trained' => ['FORMEE', null, null],
]);

test('the client and the team see the procedure of a demande on their pages and lists', function () {
    $client = Client::factory()->create();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'DEVIS_ENVOYE']);
    Contrat::factory()->create(['client_id' => $client->id, 'demande_id' => $demande->id, 'statut' => 'ENVOYE']);

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.demandes.show', $demande))
        ->assertInertia(fn ($page) => $page
            ->where('parcours.prochaine.aVous', true)
            ->where('parcours.prochaine.action', 'signer_contrat'));

    $this->actingAs($client->user, 'web')
        ->get(route('espace-client.demandes.index'))
        ->assertInertia(fn ($page) => $page
            ->where('demandes.0.prochaineEtape.titre', 'Signer votre contrat')
            ->where('demandes.0.prochaineEtape.aVous', true));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.show', $demande))
        ->assertInertia(fn ($page) => $page
            ->where('parcours.prochaine.aVous', false)
            ->where('parcours.prochaine.libelleActeur', 'Client'));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.demandes.index'))
        ->assertInertia(fn ($page) => $page
            ->where('demandes.0.prochaineEtape.titre', 'Signature du client')
            ->where('demandes.0.prochaineEtape.aVous', false));
});

test('a demande handled under the earlier procedure is never sent back to a passed step', function () {
    $terminee = Demande::factory()->create(['statut' => 'TERMINEE']);
    Contrat::factory()->signe()->create(['demande_id' => $terminee->id]);

    expect(ParcoursDemande::pour($terminee->fresh(), 'admin')['termine'])->toBeTrue();

    // Paid before its contract existed: the prestation cannot start without
    // it, so getting the contract out is what DAG has to do.
    $payeeSansContrat = Demande::factory()->create(['statut' => 'PAYEE']);
    Facture::factory()->payee()->create(['demande_id' => $payeeSansContrat->id]);
    $parcours = ParcoursDemande::pour($payeeSansContrat->fresh(), 'admin');

    expect(etapeEnCours($parcours)[0])->toBe('prestation');
    expect($parcours['prochaine']['cle'])->toBe('signature');
    expect($parcours['prochaine']['acteur'])->toBe('dag');
    expect(collect($parcours['etapes'])->where('etat', 'faite')->pluck('cle')->all())
        ->toBe(['demande', 'devis', 'signature', 'validation', 'paiement']);
});

test('an enrolment paid before dossiers existed waits for DAG to confirm the seat, not for a payment', function () {
    $inscription = Inscription::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => ($session = FormationSession::factory()->create())->formation_id,
        'formation_session_id' => $session->id,
        'montant_negocie' => 40000,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);
    Facture::factory()->payee()->create(['client_id' => null, 'apprenant_id' => $inscription->apprenant_id, 'inscription_id' => $inscription->id]);

    $pourApprenant = ParcoursInscription::pour($inscription->fresh(), 'client');
    $pourAdmin = ParcoursInscription::pour($inscription->fresh(), 'admin');

    expect(etapeEnCours($pourApprenant)[0])->toBe('formation');
    expect($pourApprenant['prochaine']['information'])->toBeFalse();
    expect($pourAdmin['prochaine']['titre'])->toBe("Confirmer l'inscription");
    expect($pourAdmin['prochaine']['aVous'])->toBeTrue();
});

test('an older enrolment never billed is not sent to a payment: DAG confirms the seat', function () {
    $session = FormationSession::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => Apprenant::factory()->create()->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $pourApprenant = ParcoursInscription::pour($inscription, 'client');
    $pourAdmin = ParcoursInscription::pour($inscription->fresh(), 'admin');

    expect(etapeEnCours($pourApprenant)[0])->toBe('formation');
    expect($pourApprenant['prochaine']['aVous'])->toBeFalse();
    expect($pourAdmin['prochaine']['action'])->toBe('confirmer_inscription');
});
