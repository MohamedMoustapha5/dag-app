<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\PieceInscription;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\DossierInscriptionNotification;
use App\Notifications\InscriptionDecisionNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * One fake file per piece required by the formation of the session.
 *
 * @return array<int, UploadedFile>
 */
function piecesPour(FormationSession $session): array
{
    return collect($session->formation->piecesRequises())
        ->map(fn (string $libelle, int $index) => UploadedFile::fake()->create("piece-{$index}.pdf", 200, 'application/pdf'))
        ->all();
}

/**
 * Enrols the apprenant through the espace with a complete dossier and
 * returns the enrolment.
 */
function inscrire(Apprenant $apprenant, FormationSession $session): Inscription
{
    test()->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session), ['pieces' => piecesPour($session)])
        ->assertRedirect(route('espace-apprenant.inscriptions'));

    return Inscription::where('apprenant_id', $apprenant->id)->sole();
}

/**
 * Enrols the apprenant and has DAG validate the dossier, which bills the fee.
 */
function inscrireEtValider(Apprenant $apprenant, FormationSession $session): Inscription
{
    $inscription = inscrire($apprenant, $session);

    test()->actingAs(MembreEquipe::factory()->promotrice()->create(), 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertOk();

    return $inscription->fresh();
}

test('enrolling submits the dossier for review without billing anything yet', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create(['prix' => 120000]);

    $inscription = inscrire($apprenant, $session);

    expect($inscription->statut)->toBe('DOSSIER_EN_ETUDE');
    expect((float) $inscription->montant_negocie)->toBe(120000.0);
    expect(Facture::count())->toBe(0);

    $pieces = PieceInscription::where('inscription_id', $inscription->id)->get();
    expect($pieces->pluck('libelle')->all())->toBe(Formation::PIECES_PAR_DEFAUT);
    $pieces->each(fn (PieceInscription $piece) => Storage::disk('local')->assertExists($piece->chemin));

    Notification::assertSentTo(
        $promotrice,
        AlerteEquipeNotification::class,
        fn (AlerteEquipeNotification $alerte) => $alerte->evenement() === 'inscription.nouvelle',
    );
});

test('the dossier must contain every piece the formation asks for', function () {
    $apprenant = Apprenant::factory()->create();
    $formation = Formation::factory()->create(['pieces_requises' => ['Copie de la CNI', 'Diplôme le plus élevé']]);
    $session = FormationSession::factory()->for($formation)->create();

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.inscriptions.create', $session))
        ->assertInertia(fn ($page) => $page->where('session.piecesRequises', ['Copie de la CNI', 'Diplôme le plus élevé']));

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session), [
            'pieces' => [0 => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf')],
        ])
        ->assertSessionHasErrors(['pieces.1' => 'Joignez la pièce : Diplôme le plus élevé.']);

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session), [
            'pieces' => [
                0 => UploadedFile::fake()->create('cni.exe', 100),
                1 => UploadedFile::fake()->create('diplome.pdf', 100, 'application/pdf'),
            ],
        ])
        ->assertSessionHasErrors('pieces.0');

    expect(Inscription::count())->toBe(0);
});

test('a session whose fee is not published yet takes no enrolment', function () {
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create(['prix' => null]);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.inscriptions.create', $session))
        ->assertInertia(fn ($page) => $page->where('session.prix', null));

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session))
        ->assertStatus(409);

    expect(Inscription::count())->toBe(0);
    expect(Facture::count())->toBe(0);
});

test('the espace catalogue shows each session fee and lets the apprenant enrol from there', function () {
    $apprenant = Apprenant::factory()->create();
    $avecPrix = FormationSession::factory()->create(['prix' => 90000]);
    FormationSession::factory()->create(['prix' => null]);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.formations'))
        ->assertInertia(function ($page) use ($avecPrix) {
            $sessions = collect($page->toArray()['props']['sessions'])->keyBy('sessionId');

            expect($sessions[$avecPrix->id]['prix'])->toBe('90000.00');
            expect($sessions[$avecPrix->id]['peutSInscrire'])->toBeTrue();
            expect($sessions[$avecPrix->id]['inscriptionUrl'])->toBe(route('espace-apprenant.inscriptions.create', $avecPrix));
            expect($sessions->where('prix', null)->first()['peutSInscrire'])->toBeFalse();
        });
});

test('a session cannot be opened to enrolments without a registration fee', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create(['statut' => 'BROUILLON', 'prix' => null]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.sessions.update', $session), [
            'ville' => $session->ville,
            'mode' => $session->mode,
            'places' => $session->places,
            'statut' => 'OUVERTE',
        ])
        ->assertSessionHasErrors(['prix' => "Indiquez les frais d'inscription avant d'ouvrir la session aux inscriptions."]);

    expect($session->fresh()->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.sessions.update', $session), [
            'ville' => $session->ville,
            'mode' => $session->mode,
            'places' => $session->places,
            'prix' => 85000,
            'statut' => 'OUVERTE',
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->statut)->toBe('OUVERTE');
    expect((float) $session->fresh()->prix)->toBe(85000.0);
});

test('validating the dossier bills the fee, and paying it confirms the seat', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create(['prix' => 50000, 'date_debut' => '2026-12-01']);
    $inscription = inscrire($apprenant, $session);

    $this->actingAs($promotrice, 'admin')
        ->getJson(route('espace-admin.sessions.inscrits', $session))
        ->assertJsonPath('inscriptions.0.statut', 'DOSSIER_EN_ETUDE')
        ->assertJsonPath('inscriptions.0.dossierComplet', true)
        ->assertJsonPath('inscriptions.0.paiement', null)
        ->assertJsonCount(3, 'inscriptions.0.pieces');

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertOk();

    $facture = Facture::sole();
    expect($inscription->fresh()->statut)->toBe('EN_ATTENTE_PAIEMENT');
    expect($inscription->fresh()->dossier_valide_at)->not->toBeNull();
    expect($facture->type)->toBe('FORMATION');
    expect($facture->apprenant_id)->toBe($apprenant->id);
    expect($facture->inscription_id)->toBe($inscription->id);
    expect((float) $facture->montant)->toBe(50000.0);
    expect($facture->echeance->toDateString())->toBe('2026-12-01');
    Notification::assertSentTo($apprenant->user, DossierInscriptionNotification::class);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.factures'))
        ->assertInertia(fn ($page) => $page
            ->where('factures.0.id', $facture->id)
            ->where('factures.0.solde', '50000'));

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.factures.paiements.store', $facture), ['montant' => 50000, 'moyen' => 'OM'])
        ->assertSessionHasNoErrors();

    expect($facture->fresh()->statut)->toBe('PAYEE');
    expect($inscription->fresh()->statut)->toBe('VALIDEE');
    Notification::assertSentTo($apprenant->user, InscriptionDecisionNotification::class);
});

test('a validated dossier cannot be validated a second time', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrireEtValider(Apprenant::factory()->create(), FormationSession::factory()->create(['prix' => 40000]));

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertStatus(409);

    expect(Facture::count())->toBe(1);
});

test('DAG can ask for a completion, and the corrected pieces send the dossier back to review', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = inscrire($apprenant, FormationSession::factory()->create());
    $ancienChemin = $inscription->pieces()->where('libelle', Formation::PIECES_PAR_DEFAUT[0])->value('chemin');

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.completer', $inscription), ['motif' => ''])
        ->assertJsonValidationErrors('motif');

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.completer', $inscription), ['motif' => 'La CNI est illisible.'])
        ->assertOk();

    expect($inscription->fresh()->statut)->toBe('DOSSIER_A_COMPLETER');
    expect($inscription->fresh()->motif_decision)->toBe('La CNI est illisible.');
    Notification::assertSentTo($apprenant->user, DossierInscriptionNotification::class);

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.inscriptions'))
        ->assertInertia(fn ($page) => $page
            ->where('inscriptions.0.statut', 'DOSSIER_A_COMPLETER')
            ->where('inscriptions.0.motif', 'La CNI est illisible.'));

    $this->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.completer', $inscription), [
            'pieces' => [0 => UploadedFile::fake()->create('cni-nette.pdf', 150, 'application/pdf')],
        ])
        ->assertRedirect(route('espace-apprenant.inscriptions'));

    $inscription->refresh();
    $piece = $inscription->pieces()->where('libelle', Formation::PIECES_PAR_DEFAUT[0])->sole();
    expect($inscription->statut)->toBe('DOSSIER_EN_ETUDE');
    expect($inscription->motif_decision)->toBeNull();
    expect($piece->nom_original)->toBe('cni-nette.pdf');
    expect($inscription->pieces()->count())->toBe(3);
    Storage::disk('local')->assertMissing($ancienChemin);
    Storage::disk('local')->assertExists($piece->chemin);
});

test('an apprenant cannot complete a dossier that is not theirs or not sent back', function () {
    $inscription = inscrire(Apprenant::factory()->create(), FormationSession::factory()->create());
    $autre = Apprenant::factory()->create();
    $piece = ['pieces' => [0 => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf')]];

    $this->actingAs($autre->user)
        ->post(route('espace-apprenant.inscriptions.completer', $inscription), $piece)
        ->assertNotFound();

    $this->actingAs($inscription->apprenant->user)
        ->post(route('espace-apprenant.inscriptions.completer', $inscription), $piece)
        ->assertStatus(409);
});

test('the pieces of a dossier are only served to the members who decide on it', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $inscription = inscrire(Apprenant::factory()->create(), FormationSession::factory()->create());
    $piece = $inscription->pieces()->firstOrFail();

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.pieces-inscription.show', $piece))
        ->assertOk()
        ->assertDownload();

    $sansDroit = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);

    $this->actingAs($sansDroit, 'admin')
        ->get(route('espace-admin.pieces-inscription.show', $piece))
        ->assertForbidden();
});

test('refusing a dossier frees the seat and tells the apprenant why', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create(['places' => 1]);
    $inscription = inscrire(Apprenant::factory()->create(), $session);

    expect($session->fresh()->placesRestantes())->toBe(0);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription), ['motif' => 'Prérequis non remplis.'])
        ->assertOk();

    expect($inscription->fresh()->statut)->toBe('ABANDONNEE');
    expect($inscription->fresh()->motif_decision)->toBe('Prérequis non remplis.');
    expect($session->fresh()->placesRestantes())->toBe(1);
    Notification::assertSentTo($inscription->apprenant->user, InscriptionDecisionNotification::class);
});

test('refusing an unpaid enrolment cancels its fee, which can no longer be paid', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = inscrireEtValider($apprenant, FormationSession::factory()->create());
    $facture = Facture::sole();

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription))
        ->assertOk();

    expect($inscription->fresh()->statut)->toBe('ABANDONNEE');
    expect($facture->fresh()->statut)->toBe('EN_ECHEC');

    $this->actingAs($apprenant->user)
        ->post(route('paiements.initier', $facture), ['moyen' => 'CARTE'])
        ->assertSessionHasErrors('moyen');
});

test('an enrolment whose fee was partly paid cannot be refused before a refund', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = inscrireEtValider($apprenant, FormationSession::factory()->create(['prix' => 60000]));
    Facture::sole()->update(['montant_paye' => 20000, 'statut' => 'PARTIELLEMENT_PAYEE']);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription))
        ->assertStatus(409);

    expect($inscription->fresh()->statut)->toBe('EN_ATTENTE_PAIEMENT');
});

test('a formation lists the pieces its dossier requires, with a default list', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $formation = Formation::factory()->create();

    expect($formation->piecesRequises())->toBe(Formation::PIECES_PAR_DEFAUT);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.formations.update', $formation), [
            'nom' => $formation->nom,
            'description' => $formation->description,
            'statut' => $formation->statut,
            'pieces_requises' => ['  Copie de la CNI ', '', 'Copie du dernier diplôme'],
        ])
        ->assertSessionHasNoErrors();

    expect($formation->fresh()->piecesRequises())->toBe(['Copie de la CNI', 'Copie du dernier diplôme']);
});

test('the public pages show the registration fee of each session', function () {
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);
    FormationSession::factory()->for($formation)->create(['prix' => 45000]);

    $this->get(route('formations'))->assertInertia(fn ($page) => $page->where('sessions.0.prix', '45000.00'));
    $this->get(route('formations.show', $formation))->assertInertia(fn ($page) => $page->where('sessions.0.prix', '45000.00'));
});
