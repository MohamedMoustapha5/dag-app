<?php

use App\Logging\AppliquerMasquage;
use App\Models\User;
use App\Support\Messagerie\JournalPasserelle;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->dossierLogs = storage_path('framework/testing/logs-'.Str::random(8));
    config([
        'logging.default' => 'daily',
        'logging.channels.daily.path' => $this->dossierLogs.'/laravel.log',
    ]);
    Log::forgetChannel('daily');
});

afterEach(function () {
    Log::forgetChannel('daily');
    File::deleteDirectory($this->dossierLogs);
});

function contenuDesLogs(string $dossier): string
{
    return collect(File::glob($dossier.'/*.log'))->map(fn (string $fichier) => File::get($fichier))->implode('');
}

function echouerAvecMotDePasse(string $motDePasse): never
{
    throw new RuntimeException('Connexion refusée pour jean.mbarga@example.com.');
}

test('secrets and personal data are masked in the message and the context, the useful facts stay', function () {
    Log::warning('Paiement DAG-FAC-2026-0042 de 20 000 FCFA le 2026-11-11 10:00 refusé pour aminata@example.com (+237 6 99 12 34 56), carte 4111 1111 1111 1111, lien https://dag.test/contrats/42?expires=1&signature=abc123def456 avec Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.secret', [
        'password' => 'MonSecret!2026',
        'cni_numero' => '112233445',
        'telephone' => '699123456',
        'requete' => ['email' => 'paul@example.com', 'remember_token' => 'tok_987654321', 'statut' => 'EN_ATTENTE'],
        'formation_session_id' => '01a18d5d-26bc-73f0-9e9a-3e7ba12c0b84',
    ]);

    $logs = contenuDesLogs($this->dossierLogs);

    expect($logs)
        ->not->toContain('MonSecret!2026')
        ->not->toContain('112233445')
        ->not->toContain('699123456')
        ->not->toContain('6 99 12 34 56')
        ->not->toContain('aminata@')
        ->not->toContain('paul@')
        ->not->toContain('4111 1111 1111 1111')
        ->not->toContain('abc123def456')
        ->not->toContain('eyJhbGciOiJIUzI1NiJ9')
        ->not->toContain('tok_987654321')
        ->toContain('DAG-FAC-2026-0042')
        ->toContain('20 000 FCFA')
        ->toContain('2026-11-11 10:00')
        ->toContain('***@example.com')
        ->toContain('EN_ATTENTE')
        ->toContain('01a18d5d-26bc-73f0-9e9a-3e7ba12c0b84');
});

test('an exception is logged with its class and place, never with the arguments of the calls', function () {
    ini_set('zend.exception_ignore_args', '0');

    try {
        echouerAvecMotDePasse('Secret-42');
    } catch (RuntimeException $exception) {
        report($exception);
    }

    $logs = contenuDesLogs($this->dossierLogs);

    expect($logs)
        ->toContain('RuntimeException')
        ->toContain('MasquageLogsTest.php')
        ->toContain('echouerAvecMotDePasse()')
        ->not->toContain('jean.mbarga@')
        ->not->toContain('Secret-42');
});

test('a database error keeps the query but not the values it was run with', function () {
    User::factory()->create(['email' => 'doublon@example.com']);

    try {
        User::factory()->create(['email' => 'doublon@example.com', 'name' => 'Nom Très Personnel']);
    } catch (QueryException $exception) {
        report($exception);
    }

    $logs = contenuDesLogs($this->dossierLogs);

    expect($logs)
        ->toContain('UniqueConstraintViolationException')
        ->toContain('insert into')
        ->not->toContain('doublon@')
        ->not->toContain('Nom Très Personnel');
});

test('the stand-in SMS provider never writes a full phone number', function () {
    (new JournalPasserelle)->envoyer('+237 699 12 34 78', 'Votre séance a lieu demain.');

    expect(contenuDesLogs($this->dossierLogs))
        ->toContain('numéro se terminant par 78')
        ->toContain('Votre séance a lieu demain.')
        ->not->toContain('699');
});

test('every channel that writes or sends logs masks sensitive data', function () {
    $canaux = collect(config('logging.channels'))
        ->except(['stack', 'null', 'emergency']);

    expect($canaux)->not->toBeEmpty();

    $canaux->each(fn (array $canal, string $nom) => expect(in_array(AppliquerMasquage::class, $canal['tap'] ?? [], true))
        ->toBeTrue("Le canal « {$nom} » n'applique pas le masquage."));
});
