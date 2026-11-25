<?php

use App\Models\Clause;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ContratDagSeeder;
use Database\Seeders\DatabaseSeeder;

test('on the production server the seeder creates the reference data but no demo account', function () {
    app()->instance('env', 'production');

    // Called directly: `db:seed` would first ask for a confirmation in production.
    app(DatabaseSeeder::class)->setContainer(app())->__invoke();

    expect(User::count())->toBe(0);
    expect(MembreEquipe::count())->toBe(5);
    expect(MembreEquipe::where('email', 'senghortams@gmail.com')->exists())->toBeTrue();
    expect(Service::count())->toBeGreaterThan(0);
    expect(Clause::count())->toBe(count(ContratDagSeeder::articles()));
    expect(ModeleContrat::where('type', 'PRESTATION_SERVICE')->exists())->toBeTrue();
});

test('on a development machine the seeder still builds the full demo', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'aicha.n@exemple.com')->exists())->toBeTrue();
    expect(Clause::count())->toBe(count(ContratDagSeeder::articles()));
});
