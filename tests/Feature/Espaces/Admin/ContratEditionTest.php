<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Notifications\ContratASignerNotification;
use Illuminate\Support\Facades\Notification;

test('the contract list tells who each contract is for and which ones await a correction', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create(['nom' => 'Aïcha Ngono']);
    $aCorriger = Contrat::factory()->create([
        'client_id' => $client->id,
        'statut' => 'BROUILLON',
        'motif_rectification' => 'Le nombre de badges est faux.',
    ]);

    $this->actingAs($promotrice, 'admin')
        ->get(route('espace-admin.contrats.index'))
        ->assertInertia(fn ($page) => $page
            ->where('contrats.0.id', $aCorriger->id)
            ->where('contrats.0.partie', 'Aïcha Ngono')
            ->where('contrats.0.rectificationDemandee', true));
});

test('a modele can be edited, and making it the default one retires the previous default of its type', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $ancien = ModeleContrat::create(['nom' => 'Ancien', 'type' => 'PRESTATION_SERVICE', 'contenu' => 'A', 'statut' => 'ACTIF']);
    $abonnement = ModeleContrat::create(['nom' => 'Abonnement', 'type' => 'ABONNEMENT', 'contenu' => 'B', 'statut' => 'ACTIF']);
    $nouveau = ModeleContrat::create(['nom' => 'Nouveau', 'type' => 'PRESTATION_SERVICE', 'contenu' => 'C', 'statut' => 'BROUILLON']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.modeles-contrat.update', $nouveau), ['nom' => 'Nouveau modèle', 'contenu' => 'Contrat entre DAG et {{client}}.'])
        ->assertSessionHasNoErrors();

    expect($nouveau->fresh()->nom)->toBe('Nouveau modèle');
    expect($nouveau->fresh()->contenu)->toBe('Contrat entre DAG et {{client}}.');
    expect($nouveau->fresh()->statut)->toBe('BROUILLON');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.modeles-contrat.update', $nouveau), ['statut' => 'ACTIF'])
        ->assertSessionHasNoErrors();

    expect($nouveau->fresh()->statut)->toBe('ACTIF');
    expect($ancien->fresh()->statut)->toBe('BROUILLON');
    expect($abonnement->fresh()->statut)->toBe('ACTIF');

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.modeles-contrat.update', $nouveau), ['contenu' => ''])
        ->assertSessionHasErrors('contenu');
});

test('a new clause goes to the end, and clauses move one article at a time', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $objet = Clause::create(['titre' => 'Objet', 'contenu' => 'x', 'ordre' => 0]);
    $cout = Clause::create(['titre' => 'Coût', 'contenu' => 'x', 'ordre' => 0]);

    $this->actingAs($promotrice, 'admin')
        ->post(route('espace-admin.clauses.store'), ['titre' => 'Délai', 'contenu' => 'x'])
        ->assertSessionHasNoErrors();

    $titres = fn () => Clause::orderBy('ordre')->orderBy('created_at')->pluck('titre')->all();
    expect($titres())->toBe(['Objet', 'Coût', 'Délai']);

    $delai = Clause::where('titre', 'Délai')->sole();
    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.clauses.deplacer', $delai), ['direction' => 'haut'])
        ->assertRedirect();
    expect($titres())->toBe(['Objet', 'Délai', 'Coût']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.clauses.deplacer', $objet), ['direction' => 'haut'])
        ->assertRedirect();
    expect($titres())->toBe(['Objet', 'Délai', 'Coût']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.clauses.deplacer', $cout), ['direction' => 'nulle-part'])
        ->assertSessionHasErrors('direction');
});

test('a read-only role cannot move a clause', function () {
    $lecture = MembreEquipe::factory()->create(['role' => MembreEquipeRole::CommunicationMarketing]);
    $clause = Clause::create(['titre' => 'Objet', 'contenu' => 'x', 'ordre' => 1]);

    $this->actingAs($lecture, 'admin')
        ->patch(route('espace-admin.clauses.deplacer', $clause), ['direction' => 'haut'])
        ->assertForbidden();
});

test('a draft contract is saved and sent to the client in one click', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $client = Client::factory()->create();
    $contrat = Contrat::factory()->create(['client_id' => $client->id, 'statut' => 'BROUILLON', 'contenu' => 'Ancien texte.']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.contenu', $contrat), [
            'contenu' => "Introduction.\n\n1. Objet\nTexte corrigé.",
            'envoyer' => true,
        ])
        ->assertSessionHasNoErrors();

    $contrat->refresh();
    expect($contrat->contenu)->toBe("Introduction.\n\n1. Objet\nTexte corrigé.");
    expect($contrat->statut)->toBe('ENVOYE');
    Notification::assertSentTo($client->user, ContratASignerNotification::class);
});

test('saving a draft without sending it keeps it in draft', function () {
    Notification::fake();
    $promotrice = MembreEquipe::factory()->promotrice()->create();
    $contrat = Contrat::factory()->create(['statut' => 'BROUILLON', 'contenu' => 'Ancien texte.']);

    $this->actingAs($promotrice, 'admin')
        ->patch(route('espace-admin.contrats.contenu', $contrat), ['contenu' => 'Nouveau texte.'])
        ->assertSessionHasNoErrors();

    expect($contrat->fresh()->statut)->toBe('BROUILLON');
    expect($contrat->fresh()->contenu)->toBe('Nouveau texte.');
    Notification::assertNothingSent();
});
