<?php

use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Models\MembreEquipe;
use App\Support\DroitsMatrix;

test('the promotrice has total access to every data domain', function () {
    foreach (Donnee::cases() as $donnee) {
        expect(DroitsMatrix::niveau(MembreEquipeRole::Promotrice, $donnee))->toBe(NiveauDroit::Total);
    }
});

test('the access matrix matches section 3.2 of the cahier des charges', function (MembreEquipeRole $role, Donnee $donnee, NiveauDroit $attendu) {
    expect(DroitsMatrix::niveau($role, $donnee))->toBe($attendu);
})->with([
    'juriste — contrats & clauses' => [MembreEquipeRole::Juriste, Donnee::ContratsClauses, NiveauDroit::Total],
    'juriste — factures & paiements' => [MembreEquipeRole::Juriste, Donnee::FacturesPaiements, NiveauDroit::Aucun],
    'communication — catalogue' => [MembreEquipeRole::CommunicationMarketing, Donnee::Catalogue, NiveauDroit::Gestion],
    'communication — factures & paiements' => [MembreEquipeRole::CommunicationMarketing, Donnee::FacturesPaiements, NiveauDroit::Aucun],
    'controle de gestion — factures & paiements' => [MembreEquipeRole::ControleGestion, Donnee::FacturesPaiements, NiveauDroit::Total],
    'controle de gestion — abonnements' => [MembreEquipeRole::ControleGestion, Donnee::Abonnements, NiveauDroit::Gestion],
    'controle de gestion — dossiers apprenants' => [MembreEquipeRole::ControleGestion, Donnee::DossiersApprenants, NiveauDroit::Aucun],
    'controle de gestion — statistiques' => [MembreEquipeRole::ControleGestion, Donnee::Statistiques, NiveauDroit::Total],
    'creation de contenu — formations' => [MembreEquipeRole::CreationContenu, Donnee::Formations, NiveauDroit::Gestion],
    'creation de contenu — demandes clients' => [MembreEquipeRole::CreationContenu, Donnee::DemandesClients, NiveauDroit::Aucun],
    'creation de contenu — entreprises & offres' => [MembreEquipeRole::CreationContenu, Donnee::EntreprisesOffres, NiveauDroit::Aucun],
]);

test('each access level only authorises its own set of actions', function (NiveauDroit $niveau, array $actionsAutorisees) {
    foreach (Action::cases() as $action) {
        expect($niveau->autorise($action))->toBe(in_array($action, $actionsAutorisees, true));
    }
})->with([
    'total' => [NiveauDroit::Total, Action::cases()],
    'gestion' => [NiveauDroit::Gestion, [Action::Voir, Action::Creer, Action::Modifier]],
    'validation' => [NiveauDroit::Validation, [Action::Voir, Action::Valider]],
    'lecture' => [NiveauDroit::Lecture, [Action::Voir, Action::Telecharger]],
    'aucun' => [NiveauDroit::Aucun, []],
]);

test('a membre equipe inherits the rights of its role', function () {
    $juriste = MembreEquipe::factory()->juriste()->create();

    expect($juriste->peut(Donnee::ContratsClauses, Action::Supprimer))->toBeTrue();
    expect($juriste->peut(Donnee::FacturesPaiements, Action::Voir))->toBeFalse();
});

test('a deactivated membre equipe has no rights regardless of role', function () {
    $promotrice = MembreEquipe::factory()->promotrice()->inactif()->create();

    expect($promotrice->peut(Donnee::Catalogue, Action::Voir))->toBeFalse();
});
