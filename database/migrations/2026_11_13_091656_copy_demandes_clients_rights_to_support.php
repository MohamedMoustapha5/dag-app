<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Support becomes its own row of the access matrix. Until now it followed
 * "Demandes clients": every level the directrice set there for a role is
 * copied to Support, so nobody gains or loses access to the inbox on the day
 * of the change. The two rows are then set independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        $maintenant = now();

        DB::table('droits_roles')
            ->where('donnee', 'DEMANDES_CLIENTS')
            ->get(['role', 'niveau'])
            ->each(function (object $droit) use ($maintenant): void {
                DB::table('droits_roles')->insertOrIgnore([
                    'role' => $droit->role,
                    'donnee' => 'SUPPORT',
                    'niveau' => $droit->niveau,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ]);
            });

        Cache::forget('droits_roles');
    }

    public function down(): void
    {
        DB::table('droits_roles')->where('donnee', 'SUPPORT')->delete();

        Cache::forget('droits_roles');
    }
};
