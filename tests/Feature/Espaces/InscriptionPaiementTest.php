<?php

use App\Models\Apprenant;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Notifications\AlerteEquipeNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Enrols the apprenant through the espace and returns the enrolment.
 */
function inscrire(Apprenant $apprenant, FormationSession $session): Inscription
{
    test()->actingAs($apprenant->user)
        ->post(route('espace-apprenant.inscriptions.store', $session))
        ->assertRedirect(route('espace-apprenant.factures'));

    return Inscription::where('apprenant_id', $apprenant->id)->sole();
}

test('enrolling bills the registration fee of the session to the apprenant', function () {
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create(['prix' => 120000, 'date_debut' => '2026-12-01']);

    $inscription = inscrire($apprenant, $session);

    $facture = Facture::sole();
    expect($inscription->statut)->toBe('EN_ATTENTE_PAIEMENT');
    expect((float) $inscription->montant_negocie)->toBe(120000.0);
    expect($facture->type)->toBe('FORMATION');
    expect($facture->apprenant_id)->toBe($apprenant->id);
    expect($facture->inscription_id)->toBe($inscription->id);
    expect((float) $facture->montant)->toBe(120000.0);
    expect($facture->statut)->toBe('EN_ATTENTE');
    expect($facture->echeance->toDateString())->toBe('2026-12-01');

    $this->actingAs($apprenant->user)
        ->get(route('espace-apprenant.factures'))
        ->assertInertia(fn ($page) => $page
            ->where('factures.0.id', $facture->id)
            ->where('factures.0.solde', '120000'));
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

test('the seat is validated in a second step, only once the fee payment is confirmed', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $session = FormationSession::factory()->create(['prix' => 50000]);
    $inscription = inscrire($apprenant, $session);
    $facture = Facture::sole();

    $this->actingAs($promotrice, 'admin')
        ->getJson(route('espace-admin.sessions.inscrits', $session))
        ->assertJsonPath('inscriptions.0.paiement.statut', 'EN_ATTENTE')
        ->assertJsonPath('inscriptions.0.paiement.url', route('espace-admin.factures.show', $facture))
        ->assertJsonPath('inscriptions.0.validable', false);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertStatus(409);
    expect($inscription->fresh()->statut)->toBe('EN_ATTENTE_PAIEMENT');

    // Step 1: the payment is confirmed in Factures & paiements.
    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.factures.paiements.store', $facture), ['montant' => 50000, 'moyen' => 'OM'])
        ->assertSessionHasNoErrors();

    expect($facture->fresh()->statut)->toBe('PAYEE');
    expect($inscription->fresh()->statut)->toBe('EN_ATTENTE_PAIEMENT');
    Notification::assertSentTo(
        $promotrice,
        AlerteEquipeNotification::class,
        fn (AlerteEquipeNotification $alerte) => $alerte->evenement() === 'inscription.payee',
    );

    // Step 2: the seat is validated.
    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.valider', $inscription))
        ->assertOk();

    expect($inscription->fresh()->statut)->toBe('VALIDEE');
});

test('refusing an unpaid enrolment cancels its fee, which can no longer be paid', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = inscrire($apprenant, FormationSession::factory()->create());
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
    $inscription = inscrire($apprenant, FormationSession::factory()->create(['prix' => 60000]));
    Facture::sole()->update(['montant_paye' => 20000, 'statut' => 'PARTIELLEMENT_PAYEE']);

    $this->actingAs($promotrice, 'admin')
        ->patchJson(route('espace-admin.inscriptions.refuser', $inscription))
        ->assertStatus(409);

    expect($inscription->fresh()->statut)->toBe('EN_ATTENTE_PAIEMENT');
});

test('the public pages show the registration fee of each session', function () {
    $formation = Formation::factory()->create(['statut' => 'PUBLIEE']);
    FormationSession::factory()->for($formation)->create(['prix' => 45000]);

    $this->get(route('formations'))->assertInertia(fn ($page) => $page->where('sessions.0.prix', '45000.00'));
    $this->get(route('formations.show', $formation))->assertInertia(fn ($page) => $page->where('sessions.0.prix', '45000.00'));
});
