<?php

use App\Enums\Action;
use App\Enums\Civilite;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Models\Facture;
use App\Models\MembreEquipe;
use App\Models\Paiement;
use App\Notifications\AccesEquipeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('the index page exposes the access matrix, confirming the juriste role grants contract management', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $response = $this->actingAs($promotrice, 'admin')->get(route('espace-admin.equipe.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('espaces/admin/equipe')
        ->has('matriceRoles', 7)
        ->where('matriceRoles.1.role', 'JURISTE')
        ->where('matriceRoles.1.droits.CONTRATS_CLAUSES', 'TOTAL')
        ->has('domaines', count(Donnee::cases())));
});

test('a promotrice can create a new membre equipe account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Pascal E.',
        'email' => 'pascal.e@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertRedirect();

    $this->assertDatabaseHas('membres_equipe', ['email' => 'pascal.e@dag.cm', 'role' => 'JURISTE', 'statut' => 'ACTIF']);
    $this->assertDatabaseMissing('users', ['email' => 'pascal.e@dag.cm']);
});

test('creating a membre equipe account rejects an already used email', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $existant = MembreEquipe::factory()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Doublon',
        'email' => $existant->email,
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertSessionHasErrors('email');
});

test('a promotrice sets a courtesy title on a new account and can change or clear it later', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'civilite' => Civilite::Madame->value,
        'name' => 'Fadimatou',
        'email' => 'fadimatou@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::ControleGestion->value,
    ])->assertRedirect();

    $membre = MembreEquipe::where('email', 'fadimatou@dag.cm')->sole();
    expect($membre->civilite)->toBe(Civilite::Madame);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['civilite' => Civilite::Monsieur->value])
        ->assertRedirect();
    expect($membre->fresh()->civilite)->toBe(Civilite::Monsieur);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['civilite' => ''])
        ->assertRedirect();
    expect($membre->fresh()->civilite)->toBeNull();
});

test('an unknown courtesy title is refused', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['civilite' => Civilite::Madame]);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['civilite' => 'DR'])
        ->assertSessionHasErrors('civilite');
    expect($membre->fresh()->civilite)->toBe(Civilite::Madame);
});

test('a promotrice can change a member role and deactivate their account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CreationContenu, 'statut' => 'ACTIF']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['role' => MembreEquipeRole::Juriste->value])
        ->assertRedirect();
    expect($membre->fresh()->role)->toBe(MembreEquipeRole::Juriste);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['statut' => 'INACTIF'])
        ->assertRedirect();
    expect($membre->fresh()->statut)->toBe('INACTIF');

    $this->actingAs($membre->fresh(), 'admin')
        ->get(route('espace-admin.dashboard'))
        ->assertForbidden();
});

test('a promotrice cannot deactivate her own account', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $promotrice), ['statut' => 'INACTIF'])
        ->assertForbidden();

    expect($promotrice->fresh()->statut)->toBe('ACTIF');
});

test('a role other than promotrice cannot manage the team', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();
    $autre = MembreEquipe::factory()->create();

    $this->actingAs($juriste, 'admin')->get(route('espace-admin.equipe.index'))->assertForbidden();

    $this->actingAs($juriste, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Intrus',
        'email' => 'intrus@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Juriste->value,
    ])->assertForbidden();

    $this->actingAs($juriste, 'admin')
        ->patch(route('espace-admin.equipe.update', $autre), ['statut' => 'INACTIF'])
        ->assertForbidden();
});

test('a promotrice cannot change her own role', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $promotrice), ['role' => MembreEquipeRole::Juriste->value])
        ->assertForbidden();

    expect($promotrice->fresh()->role)->toBe(MembreEquipeRole::Promotrice);
});

test('a promotrice can edit what a role may do, and members of that role follow the new rights', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();

    expect($juriste->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();

    $this->actingAs($promotrice, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'JURISTE',
        'donnee' => 'FACTURES_PAIEMENTS',
        'niveau' => 'LECTURE',
    ])->assertRedirect();

    expect($juriste->fresh()->peut(Donnee::FacturesPaiements, Action::Voir))->toBeTrue();

    $this->actingAs($promotrice, 'admin')->get(route('espace-admin.equipe.index'))
        ->assertInertia(fn ($page) => $page
            ->where('matriceRoles.1.droits.FACTURES_PAIEMENTS', 'LECTURE')
            ->where('matriceRoles.1.personnalises', ['FACTURES_PAIEMENTS']));

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.equipe.droits.reset', 'JURISTE'))
        ->assertRedirect();

    expect($juriste->fresh()->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();
});

test("the directrice's own rights cannot be edited and only she can edit rights", function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $juriste = MembreEquipe::factory()->juriste()->create();

    $this->actingAs($promotrice, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'PROMOTRICE',
        'donnee' => 'CATALOGUE',
        'niveau' => 'AUCUN',
    ])->assertSessionHasErrors('role');

    $this->actingAs($juriste, 'admin')->put(route('espace-admin.equipe.droits.update'), [
        'role' => 'JURISTE',
        'donnee' => 'FACTURES_PAIEMENTS',
        'niveau' => 'TOTAL',
    ])->assertForbidden();
});

test('a promotrice can create a developpeur account, who starts with read-only rights', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Mustapha',
        'email' => 'dev@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Developpeur->value,
    ])->assertRedirect();

    $developpeur = MembreEquipe::where('email', 'dev@dag.cm')->firstOrFail();

    expect($developpeur->role)->toBe(MembreEquipeRole::Developpeur);
    expect($developpeur->role->label())->toBe('Développeur');
    expect($developpeur->peut(Donnee::FacturesPaiements, Action::Voir))->toBeTrue();
    expect($developpeur->peut(Donnee::FacturesPaiements, Action::Modifier))->toBeFalse();
    expect($developpeur->peut(Donnee::ContratsClauses, Action::Telecharger))->toBeTrue();
});

test('the developpeur can open the admin space but cannot manage the team', function () {
    $developpeur = MembreEquipe::factory()->developpeur()->create();

    $this->actingAs($developpeur, 'admin')
        ->get(route('espace-admin.statistiques'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('membre.roleLabel', 'Développeur')->where('onglets', ['indicateurs', 'documents']));

    $this->actingAs($developpeur, 'admin')
        ->get(route('espace-admin.equipe.index'))
        ->assertForbidden();
});

test('a promotrice edits the name and e-mail of a member', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['name' => 'Ancien Nom', 'email' => 'ancien@dag.cm']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['name' => 'Awa Diallo', 'email' => ' Awa.Diallo@DAG.cm '])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $membre->refresh();
    expect($membre->name)->toBe('Awa Diallo');
    expect($membre->email)->toBe('awa.diallo@dag.cm');
});

test('a member cannot be given the e-mail of another member', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    MembreEquipe::factory()->create(['email' => 'prise@dag.cm']);
    $membre = MembreEquipe::factory()->create(['email' => 'membre@dag.cm']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['name' => $membre->name, 'email' => 'prise@dag.cm'])
        ->assertSessionHasErrors(['email' => 'Cette adresse e-mail est déjà utilisée par un autre membre de l\'équipe.']);

    expect($membre->fresh()->email)->toBe('membre@dag.cm');
});

test('a promotrice deletes a member: the account goes, its history stays', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['name' => 'Ancien Membre']);
    $paiement = Paiement::create([
        'facture_id' => Facture::factory()->create()->id, 'montant' => 5000, 'moyen' => 'ESPECES',
        'statut' => 'CONFIRME', 'valide_par_id' => $membre->id,
    ]);
    DB::table('sessions')->insert(['id' => 'session-du-membre', 'user_id' => $membre->id, 'payload' => '', 'last_activity' => now()->timestamp]);

    $this->actingAs($promotrice, 'admin')
        ->delete(route('espace-admin.equipe.destroy', $membre))
        ->assertRedirect();

    $this->assertModelMissing($membre);
    $this->assertModelExists($paiement);
    expect($paiement->fresh()->valide_par_id)->toBeNull();
    $this->assertDatabaseMissing('sessions', ['id' => 'session-du-membre']);
    $this->assertDatabaseHas('activity_logs', ['membre_equipe_id' => $promotrice->id, 'action' => 'a supprimé le compte de']);
});

test('a promotrice cannot delete her own account and other roles cannot delete anyone', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $membre = MembreEquipe::factory()->create();

    $this->actingAs($promotrice, 'admin')->delete(route('espace-admin.equipe.destroy', $promotrice))->assertForbidden();
    $this->actingAs($marketing, 'admin')->delete(route('espace-admin.equipe.destroy', $membre))->assertForbidden();
    $this->actingAs($marketing, 'admin')
        ->patch(route('espace-admin.equipe.update', $membre), ['name' => 'Piraté'])
        ->assertForbidden();

    $this->assertModelExists($promotrice);
    $this->assertModelExists($membre);
    expect($membre->fresh()->name)->not->toBe('Piraté');
});

test('a promotrice can create a cybersecurity account, which reads the platform but not the finances', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();

    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.store'), [
        'name' => 'Expert Sécurité',
        'email' => 'cyber@dag.cm',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'role' => MembreEquipeRole::Cybersecurite->value,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $cyber = MembreEquipe::where('email', 'cyber@dag.cm')->firstOrFail();

    expect($cyber->role->label())->toBe('Cybersécurité');
    expect($cyber->peut(Donnee::Statistiques, Action::Voir))->toBeTrue();
    expect($cyber->peut(Donnee::DemandesClients, Action::Voir))->toBeTrue();
    expect($cyber->peut(Donnee::DemandesClients, Action::Modifier))->toBeFalse();
    expect($cyber->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();
});

test('a promotrice e-mails a member a new random password, which replaces the old one and ends their sessions', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $membre = MembreEquipe::factory()->create(['email' => 'nouvelle.adresse@dag.cm']);
    DB::table('sessions')->insert(['id' => 'ancienne-session', 'user_id' => $membre->id, 'payload' => '', 'last_activity' => now()->timestamp]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.equipe.acces', $membre))
        ->assertRedirect();

    $motDePasse = Notification::sent($membre, AccesEquipeNotification::class)->sole()->motDePasse;
    expect(Hash::check($motDePasse, $membre->fresh()->password))->toBeTrue();
    expect(Hash::check('password', $membre->fresh()->password))->toBeFalse();
    $this->assertDatabaseMissing('sessions', ['id' => 'ancienne-session']);
    $this->assertDatabaseHas('activity_logs', ['membre_equipe_id' => $promotrice->id, 'action' => 'a envoyé de nouveaux accès à']);
});

test('new access is only sent by the promotrice, to an active member other than herself', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $marketing = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $inactif = MembreEquipe::factory()->inactif()->create();

    $this->actingAs($marketing, 'admin')->post(route('espace-admin.equipe.acces', $promotrice))->assertForbidden();
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.acces', $promotrice))->assertForbidden();
    $this->actingAs($promotrice, 'admin')->post(route('espace-admin.equipe.acces', $inactif))->assertRedirect();

    Notification::assertNothingSent();
    expect(Hash::check('password', $inactif->fresh()->password))->toBeTrue();
});
