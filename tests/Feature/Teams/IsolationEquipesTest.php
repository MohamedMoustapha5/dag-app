<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

/*
 * Isolation between teams: the owner of team A has every right on A and
 * none on team B — neither on B's own routes nor by naming a member or an
 * invitation of B in a route of A.
 */

beforeEach(function () {
    $this->proprietaireA = User::factory()->create();
    $this->equipeA = Team::factory()->create(['name' => 'Équipe A']);
    $this->equipeA->members()->attach($this->proprietaireA, ['role' => TeamRole::Owner->value]);

    $this->proprietaireB = User::factory()->create();
    $this->membreB = User::factory()->create();
    $this->equipeB = Team::factory()->create(['name' => 'Équipe B']);
    $this->equipeB->members()->attach($this->proprietaireB, ['role' => TeamRole::Owner->value]);
    $this->equipeB->members()->attach($this->membreB, ['role' => TeamRole::Member->value]);
    $this->membreB->switchTeam($this->equipeB);

    $this->invitationB = TeamInvitation::factory()->create([
        'team_id' => $this->equipeB->id,
        'email' => 'recrue@example.com',
        'invited_by' => $this->proprietaireB->id,
    ]);
});

/**
 * Team B is exactly as it was: name, members, roles and pending invitation.
 */
function equipeBIntacte(mixed $test): void
{
    $equipe = $test->equipeB->fresh();

    expect($equipe)->not->toBeNull()
        ->and($equipe->name)->toBe('Équipe B')
        ->and($equipe->members()->count())->toBe(2)
        ->and($test->membreB->fresh()->teamRole($equipe))->toBe(TeamRole::Member)
        ->and($test->membreB->fresh()->isCurrentTeam($equipe))->toBeTrue()
        ->and(TeamInvitation::whereKey($test->invitationB->id)->exists())->toBeTrue();
}

test('the owner of another team can neither see nor act on any route of a team they do not belong to', function (string $methode, string $route, Closure $parametres, array $donnees) {
    $reponse = $this->actingAs($this->proprietaireA)
        ->call($methode, route($route, $parametres($this)), $donnees);

    $reponse->assertForbidden();
    equipeBIntacte($this);
    expect($this->proprietaireA->fresh()->isCurrentTeam($this->equipeB))->toBeFalse();
})->with([
    'tableau de bord' => ['GET', 'dashboard', fn ($t) => ['current_team' => $t->equipeB], []],
    'réglages' => ['GET', 'teams.edit', fn ($t) => $t->equipeB, []],
    'renommer' => ['PATCH', 'teams.update', fn ($t) => $t->equipeB, ['name' => 'Piratée']],
    'supprimer' => ['DELETE', 'teams.destroy', fn ($t) => $t->equipeB, ['name' => 'Équipe B']],
    'basculer dessus' => ['POST', 'teams.switch', fn ($t) => $t->equipeB, []],
    'la quitter' => ['DELETE', 'teams.leave', fn ($t) => $t->equipeB, []],
    'changer un rôle' => ['PATCH', 'teams.members.update', fn ($t) => [$t->equipeB, $t->membreB], ['role' => TeamRole::Admin->value]],
    'retirer un membre' => ['DELETE', 'teams.members.destroy', fn ($t) => [$t->equipeB, $t->membreB], []],
    'inviter' => ['POST', 'teams.invitations.store', fn ($t) => $t->equipeB, ['email' => 'complice@example.com', 'role' => TeamRole::Member->value]],
    'annuler une invitation' => ['DELETE', 'teams.invitations.destroy', fn ($t) => [$t->equipeB, $t->invitationB], []],
]);

test('a member or an invitation of another team cannot be reached through the routes of your own team', function (string $methode, string $route, Closure $parametres, array $donnees) {
    $this->actingAs($this->proprietaireA)
        ->call($methode, route($route, $parametres($this)), $donnees)
        ->assertNotFound();

    equipeBIntacte($this);
    expect($this->membreB->fresh()->belongsToTeam($this->equipeA))->toBeFalse();
})->with([
    'changer le rôle d’un membre de B' => ['PATCH', 'teams.members.update', fn ($t) => [$t->equipeA, $t->membreB], ['role' => TeamRole::Admin->value]],
    'retirer un membre de B' => ['DELETE', 'teams.members.destroy', fn ($t) => [$t->equipeA, $t->membreB], []],
    'annuler une invitation de B' => ['DELETE', 'teams.invitations.destroy', fn ($t) => [$t->equipeA, $t->invitationB], []],
]);

test('an invitation to another team cannot be answered by someone it was not sent to', function (string $route, string $methode) {
    $this->actingAs($this->proprietaireA)
        ->call($methode, route($route, $this->invitationB))
        ->assertSessionHasErrors('invitation');

    equipeBIntacte($this);
    expect($this->proprietaireA->fresh()->belongsToTeam($this->equipeB))->toBeFalse();
})->with([
    'accepter' => ['invitations.accept', 'POST'],
    'refuser' => ['invitations.decline', 'DELETE'],
]);

test('the list of teams only shows the teams you belong to', function () {
    $this->actingAs($this->proprietaireA)
        ->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('teams', fn ($equipes) => collect($equipes)->pluck('name')->doesntContain('Équipe B')
            && collect($equipes)->pluck('name')->contains('Équipe A')));
});
