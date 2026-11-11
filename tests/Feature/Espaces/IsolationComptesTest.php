<?php

use App\Models\Abonnement;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Candidature;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Formule;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\OffreEmploi;
use App\Models\TicketSupport;
use Illuminate\Routing\Route as Definition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/*
 * Isolation between kinds of account (client, entreprise partner, apprenant,
 * DAG team member, guest) and between two accounts of the same kind.
 *
 * Every route of every espace is called — read and write — with records that
 * really exist and belong to someone else: a refusal therefore comes from the
 * access rule, never from a record that cannot be found. A route added to an
 * espace later is covered without touching this file; a new route parameter
 * makes the test fail until its owner is declared below.
 */

beforeEach(function () {
    $this->client = Client::factory()->create();
    $this->entreprise = EntreprisePartenaire::factory()->create(['client_id' => Client::factory()->create()->id]);
    $this->apprenant = Apprenant::factory()->create();

    $session = FormationSession::factory()->create();
    $inscription = Inscription::create([
        'apprenant_id' => $this->apprenant->id,
        'formation_id' => $session->formation_id,
        'formation_session_id' => $session->id,
        'statut' => 'VALIDEE',
    ]);
    $offre = OffreEmploi::factory()->create(['entreprise_id' => $this->entreprise->id]);

    // The records each espace's routes take, owned by the accounts above.
    $this->ressources = [
        'espace-client' => [
            'demande' => Demande::factory()->create(['client_id' => $this->client->id]),
            'abonnement' => Abonnement::create([
                'client_id' => $this->client->id,
                'formule_id' => Formule::factory()->create()->id,
                'montant_negocie' => 45000,
                'date_souscription' => now()->toDateString(),
                'statut' => 'ACTIF',
            ]),
            'facture' => Facture::factory()->payee()->create(['client_id' => $this->client->id]),
            'contrat' => Contrat::factory()->signe()->create(['client_id' => $this->client->id]),
            'ticket' => TicketSupport::factory()->create(['user_id' => $this->client->user_id]),
            'offre' => $offre,
            'candidature' => Candidature::factory()->create(['offre_id' => $offre->id, 'apprenant_id' => $this->apprenant->id]),
        ],
        'espace-apprenant' => [
            'formationSession' => $session,
            'inscription' => $inscription,
            'facture' => Facture::factory()->payee()->create(['client_id' => null, 'apprenant_id' => $this->apprenant->id, 'inscription_id' => $inscription->id]),
            'attestation' => Attestation::create([
                'apprenant_id' => $this->apprenant->id,
                'formation_id' => Formation::factory()->create()->id,
                'date_emission' => now()->toDateString(),
            ]),
            'ticket' => TicketSupport::factory()->create(['user_id' => $this->apprenant->user_id]),
        ],
    ];
});

/**
 * @return Collection<int, Definition>
 */
function routesDeLEspace(string $espace): Collection
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (Definition $route) => str_starts_with((string) $route->getName(), $espace.'.'))
        ->values();
}

/**
 * Calls the route with the given records for its parameters (a random
 * identifier for the admin espace, refused before any record is read).
 *
 * @param  array<string, mixed>  $ressources
 */
function appelerRoute(mixed $test, Definition $route, array $ressources): TestResponse
{
    $parametres = collect($route->parameterNames())->mapWithKeys(function (string $nom) use ($route, $ressources) {
        if (str_starts_with((string) $route->getName(), 'espace-admin.')) {
            return [$nom => (string) Str::uuid()];
        }

        expect(array_key_exists($nom, $ressources))
            ->toBeTrue("Le paramètre {{$nom}} de {$route->getName()} n'a pas de ressource de test : déclarez à qui il appartient.");

        return [$nom => $ressources[$nom]];
    })->all();

    $methode = collect($route->methods())->reject(fn (string $m) => $m === 'HEAD')->first();

    return $test->call($methode, route($route->getName(), $parametres));
}

function libelleRoute(Definition $route): string
{
    return implode('|', array_diff($route->methods(), ['HEAD'])).' '.$route->getName();
}

/**
 * A DAG team member signed in to the administration only: the client and
 * apprenant espaces do not know them.
 */
function connecterMembreAdmin(MembreEquipe $membre): void
{
    auth()->guard('admin')->setUser($membre);
}

test('no account of another kind reaches any route of the espace client, read or write', function () {
    $routes = routesDeLEspace('espace-client');
    expect($routes)->not->toBeEmpty();

    $this->actingAs($this->apprenant->user);
    foreach ($routes as $route) {
        expect(appelerRoute($this, $route, $this->ressources['espace-client'])->status())
            ->toBe(403, 'Un apprenant atteint '.libelleRoute($route));
    }
});

test('no account of another kind reaches any route of the espace apprenant, read or write', function (string $compte) {
    $utilisateur = $compte === 'client' ? $this->client->user : $this->entreprise->client->user;
    $this->actingAs($utilisateur);

    foreach (routesDeLEspace('espace-apprenant') as $route) {
        expect(appelerRoute($this, $route, $this->ressources['espace-apprenant'])->status())
            ->toBe(403, "Un compte {$compte} atteint ".libelleRoute($route));
    }
})->with(['client', 'entreprise']);

test('a DAG team member signed in to the administration is a stranger to the client and apprenant espaces', function () {
    connecterMembreAdmin(MembreEquipe::factory()->promotrice()->create());

    foreach (['espace-client', 'espace-apprenant'] as $espace) {
        foreach (routesDeLEspace($espace) as $route) {
            appelerRoute($this, $route, $this->ressources[$espace])
                ->assertRedirect(route('login'), 'Un membre de l’équipe atteint '.libelleRoute($route));
        }
    }
});

test('no client, entreprise or apprenant account reaches any route of the administration', function (string $compte) {
    $utilisateur = match ($compte) {
        'client' => $this->client->user,
        'entreprise' => $this->entreprise->client->user,
        default => $this->apprenant->user,
    };
    $this->actingAs($utilisateur);

    $routes = routesDeLEspace('espace-admin');
    expect($routes->count())->toBeGreaterThan(50);

    foreach ($routes as $route) {
        appelerRoute($this, $route, [])
            ->assertRedirect(route('admin.connexion'), "Un compte {$compte} atteint ".libelleRoute($route));
    }
})->with(['client', 'entreprise', 'apprenant']);

test('a guest reaches no route of any espace', function () {
    foreach (['espace-client' => 'login', 'espace-apprenant' => 'login', 'espace-admin' => 'admin.connexion'] as $espace => $connexion) {
        foreach (routesDeLEspace($espace) as $route) {
            appelerRoute($this, $route, $this->ressources[$espace] ?? [])
                ->assertRedirect(route($connexion), 'Un visiteur atteint '.libelleRoute($route));
        }
    }
});

test('the recruitment routes refuse a private client and an entreprise not yet validated', function (string $compte) {
    $utilisateur = $compte === 'particulier'
        ? $this->client->user
        : EntreprisePartenaire::factory()->enAttente()->create(['client_id' => Client::factory()->create()->id])->client->user;
    $this->actingAs($utilisateur);

    $routes = routesDeLEspace('espace-client')
        ->filter(fn (Definition $route) => in_array('App\Http\Middleware\EnsurePartenaireValide', $route->gatherMiddleware(), true)
            || in_array('partenaire.valide', $route->gatherMiddleware(), true));
    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect(appelerRoute($this, $route, $this->ressources['espace-client'])->status())
            ->toBe(403, "Un compte {$compte} atteint ".libelleRoute($route));
    }
})->with(['particulier', 'entreprise en attente']);

test('another account of the same kind never reaches a record that is not theirs', function (string $espace) {
    $intrus = match ($espace) {
        // A validated partner: it passes the recruitment gate, so the
        // ownership of each record is what is tested.
        'espace-client' => EntreprisePartenaire::factory()->create(['client_id' => Client::factory()->create()->id])->client->user,
        default => Apprenant::factory()->create()->user,
    };
    $this->actingAs($intrus);

    // A formation session is open to every apprenant: it belongs to no one.
    $routes = routesDeLEspace($espace)
        ->filter(fn (Definition $route) => $route->parameterNames() !== [] && ! in_array('formationSession', $route->parameterNames(), true));
    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect(appelerRoute($this, $route, $this->ressources[$espace])->status())
            ->toBeIn([403, 404], 'Un autre compte atteint '.libelleRoute($route));
    }
})->with(['espace-client', 'espace-apprenant']);
