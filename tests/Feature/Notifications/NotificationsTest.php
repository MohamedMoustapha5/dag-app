<?php

use App\Enums\MembreEquipeRole;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\NotificationLog;
use App\Models\OffreEmploi;
use App\Models\Paiement;
use App\Models\Seance;
use App\Models\Service;
use App\Notifications\AlerteEquipeNotification;
use App\Notifications\AttestationDisponibleNotification;
use App\Notifications\Canaux\SmsChannel;
use App\Notifications\CandidatureTransmiseNotification;
use App\Notifications\ContratASignerNotification;
use App\Notifications\ContratSigneNotification;
use App\Notifications\DevisRecuNotification;
use App\Notifications\InscriptionDecisionNotification;
use App\Notifications\PaiementConfirmeNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('a devis sent to a client notifies them by e-mail, unless they switched it off', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 150000]);

    expect((new DevisRecuNotification($demande))->via($client->user))->toBe(['mail']);

    $client->user->update(['notif_devis' => false]);

    expect((new DevisRecuNotification($demande))->via($client->user->fresh()))->toBe([]);
});

test('the devis e-mail carries the amount, the message and a link to pay', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 150000, 'message_devis' => 'Livraison sous 3 semaines.']);

    $mail = (new DevisRecuNotification($demande))->toMail($client->user);

    expect($mail->subject)->toContain($demande->reference);
    expect($mail->introLines)->toContain('Livraison sous 3 semaines.');
    expect(implode(' ', $mail->introLines))->toContain('150 000 FCFA');
    expect($mail->actionUrl)->toBe(route('espace-client.demandes.show', $demande));
});

test('every sent notification is kept in the journal with its channel and recipient', function () {
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);

    Notification::sendNow($client->user, new DevisRecuNotification($demande));

    $log = NotificationLog::firstOrFail();
    expect($log->canal)->toBe('EMAIL');
    expect($log->statut)->toBe('ENVOYE');
    expect($log->evenement)->toBe('devis.recu');
    expect($log->destinataire)->toBe($client->user->email);
    expect($log->user_id)->toBe($client->user->id);
});

test('the sms channel is wired but stays off until it is enabled and the recipient has a phone number', function () {
    $client = Client::factory()->create(['telephone' => '+237 690000000']);
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);
    $notification = new DevisRecuNotification($demande);

    expect($notification->via($client->user))->toBe(['mail']);

    config(['notifications.canaux.sms' => true]);
    expect($notification->via($client->user))->toBe(['mail', SmsChannel::class]);

    $client->update(['telephone' => null]);
    expect($notification->via($client->user->fresh()))->toBe(['mail']);
});

test('a failing mail server never breaks the action that triggered the notification', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
    ]);
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);

    Notifier::envoyer($client->user, new DevisRecuNotification($demande));

    expect(true)->toBeTrue();
});

test('the admin sending a devis notifies the client', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'statut' => 'EN_ETUDE']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.demandes.devis', $demande), ['montant' => 120000])
        ->assertRedirect();

    Notification::assertSentTo($client->user, DevisRecuNotification::class);
});

test('a new demande alerts the team members allowed to handle demandes, and only them', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $juriste = MembreEquipe::factory()->juriste()->create();
    $client = Client::factory()->create();

    $this->actingAs($client->user)->post(route('espace-client.demandes.store'), [
        'type' => 'service',
        'service_id' => Service::factory()->create()->id,
        'description' => 'Un site vitrine.',
    ])->assertRedirect();

    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);
    Notification::assertSentTo($marketing, AlerteEquipeNotification::class);
    Notification::assertNotSentTo($juriste, AlerteEquipeNotification::class);
});

test('a confirmed payment notifies the payer and the team', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $facture = Facture::factory()->create(['client_id' => $client->id, 'montant' => 50000]);

    $this->actingAs($client->user)->post(route('paiements.initier', $facture), ['moyen' => 'CARTE']);
    $this->actingAs($client->user)
        ->post(route('paiements.simulation.decision', Paiement::firstOrFail()), ['resultat' => 'succes']);

    Notification::assertSentTo($client->user, PaiementConfirmeNotification::class);
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);
});

test('sending the contract tells the client it is ready, and signing confirms to the client and the team', function () {
    Notification::fake();
    Storage::fake('local');
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.update', $contrat), ['statut' => 'ENVOYE'])
        ->assertRedirect();

    Notification::assertSentTo($client->user, ContratASignerNotification::class);

    $this->actingAs($client->user, 'web')->post(route('espace-client.contrats.signer', $contrat), [
        'signature' => UploadedFile::fake()->image('signature.png'),
        'nom' => 'Aïcha Ngono',
        'accepte' => true,
    ])->assertRedirect();

    Notification::assertSentTo($client->user, ContratSigneNotification::class);
    Notification::assertSentTo($promotrice, AlerteEquipeNotification::class);
});

test('the apprenant is notified of the decision on their inscription and of their attestation', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'EN_ATTENTE_PAIEMENT',
    ]);

    $this->actingAs($promotrice, 'admin')->patchJson(route('espace-admin.inscriptions.valider', $inscription))->assertOk();
    Notification::assertSentTo($apprenant->user, InscriptionDecisionNotification::class);

    $inscription->update(['statut' => 'FORMEE']);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => '2026-01-10']);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.attestations.store'), ['inscription_id' => $inscription->id])
        ->assertRedirect();

    Notification::assertSentTo($apprenant->user, AttestationDisponibleNotification::class);
});

test('the entreprise is told when DAG submits a candidate', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $entreprise = EntreprisePartenaire::factory()->create(['client_id' => $client->id]);
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $entreprise->id, 'statut' => 'PUBLIEE']);
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id])
        ->assertRedirect();

    Notification::assertSentTo($client->user, CandidatureTransmiseNotification::class);
});

test('a partner entreprise without an account is notified at its own address', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $entreprise = EntreprisePartenaire::factory()->create(['client_id' => null, 'email' => 'rh@acme.cm']);
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $entreprise->id, 'statut' => 'PUBLIEE']);
    $apprenant = Apprenant::factory()->create();

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.offres.postuler', $offre), ['apprenant_id' => $apprenant->id]);

    Notification::assertSentTo($entreprise, CandidatureTransmiseNotification::class);
});

test('the séance reminder goes out once to the enrolled apprenants the day before', function () {
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $abandon = Apprenant::factory()->create();
    foreach ([[$apprenant, 'VALIDEE'], [$abandon, 'ABANDONNEE']] as [$personne, $statut]) {
        Inscription::create([
            'apprenant_id' => $personne->id,
            'formation_id' => $session->formation_id,
            'formation_session_id' => $session->id,
            'statut' => $statut,
        ]);
    }
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 1', 'date' => now()->addDay()->toDateString()]);
    Seance::create(['formation_session_id' => $session->id, 'titre' => 'Séance 2', 'date' => now()->addDays(3)->toDateString()]);

    Artisan::call('notifications:rappels-seances');
    Artisan::call('notifications:rappels-seances');

    expect(NotificationLog::where('evenement', 'seance.rappel')->count())->toBe(1);
    expect(NotificationLog::where('evenement', 'seance.rappel')->first()->user_id)->toBe($apprenant->user_id);
});

test('an apprenant who switched seance notifications off gets no reminder', function () {
    $session = FormationSession::factory()->create();
    $apprenant = Apprenant::factory()->create();
    $apprenant->user->update(['notif_seances' => false]);
    Inscription::create([
        'apprenant_id' => $apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);
    Seance::create(['formation_session_id' => $session->id, 'date' => now()->addDay()->toDateString()]);

    Artisan::call('notifications:rappels-seances');

    expect(NotificationLog::count())->toBe(0);
});

test('a contract left unsigned for days is reminded, without touching its sent date', function () {
    $client = Client::factory()->create();
    $ancien = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE']);
    $recent = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'ENVOYE']);
    $signe = Contrat::factory()->signe()->create(['client_id' => $client->id]);

    $envoyeLe = now()->subDays(5)->startOfSecond();
    Contrat::query()->whereKey([$ancien->id, $signe->id])->toBase()->update(['updated_at' => $envoyeLe]);

    Artisan::call('notifications:relancer-contrats');

    expect(NotificationLog::where('evenement', 'contrat.relance')->count())->toBe(1);

    $ancien->refresh();
    expect($ancien->derniere_relance_at)->not->toBeNull();
    expect($ancien->updated_at->equalTo($envoyeLe))->toBeTrue();
    expect($recent->fresh()->derniere_relance_at)->toBeNull();

    Artisan::call('notifications:relancer-contrats');
    expect(NotificationLog::where('evenement', 'contrat.relance')->count())->toBe(1);
});

test('the directrice sees the notifications tab and other roles do not', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();
    $client = Client::factory()->create();
    $demande = Demande::factory()->create(['client_id' => $client->id, 'montant_negocie' => 90000]);
    Notification::sendNow($client->user, new DevisRecuNotification($demande));

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'notifications']))
        ->assertInertia(fn ($page) => $page
            ->where('onglet', 'notifications')
            ->where('notifications.total', 1)
            ->where('notifications.entrees.0.statut', 'ENVOYE')
            ->where('notifications.canauxActifs.email', true)
            ->where('notifications.canauxActifs.sms', false));

    $this->actingAs($juriste, 'admin')
        ->get(route('espace-admin.statistiques', ['onglet' => 'notifications']))
        ->assertInertia(fn ($page) => $page->where('onglet', 'indicateurs')->where('notifications', null));
});
