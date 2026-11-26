<?php

use App\Enums\MembreEquipeRole;
use App\Models\Clause;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AccesEquipeNotification;
use Database\Seeders\ContratDagSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MembresEquipeSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('on the production server the seeder creates the reference data but no demo account', function () {
    Notification::fake();
    app()->instance('env', 'production');

    // Called directly: `db:seed` would first ask for a confirmation in production.
    app(DatabaseSeeder::class)->setContainer(app())->__invoke();

    expect(User::count())->toBe(0);
    expect(MembreEquipe::count())->toBe(5);
    expect(MembreEquipe::where('email', 'senghortams@gmail.com')->exists())->toBeTrue();

    // No account keeps « password »: each member is e-mailed its own, and the
    // administration address gets every right without showing on the site.
    MembreEquipe::all()->each(function (MembreEquipe $membre) {
        expect(Hash::check('password', $membre->password))->toBeFalse();
        Notification::assertSentToTimes($membre, AccesEquipeNotification::class, 1);
    });
    $administrateur = MembreEquipe::where('email', config('entreprise.administrateur.email'))->sole();
    expect($administrateur->role)->toBe(MembreEquipeRole::Promotrice);
    expect($administrateur->visible_sur_le_site)->toBeFalse();

    // Seeding the team again creates no account, so nobody's password is reset.
    app(MembresEquipeSeeder::class)->setContainer(app())->__invoke();
    Notification::assertSentTimes(AccesEquipeNotification::class, 5);

    expect(Service::count())->toBeGreaterThan(0);
    expect(Clause::count())->toBe(count(ContratDagSeeder::articles()));
    expect(ModeleContrat::where('type', 'PRESTATION_SERVICE')->exists())->toBeTrue();
});

test('on a development machine the seeder still builds the full demo', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'aicha.n@exemple.com')->exists())->toBeTrue();
    expect(Clause::count())->toBe(count(ContratDagSeeder::articles()));
});
