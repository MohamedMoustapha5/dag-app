<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Offers are no longer published by DAG: only DAG applies to them, so
     * they are open from the moment the entreprise sends them. Offers still
     * waiting for publication are opened, or nothing could open them.
     */
    public function up(): void
    {
        DB::table('offres_emploi')
            ->where('statut', 'BROUILLON')
            ->update(['statut' => 'PUBLIEE', 'updated_at' => now()]);
    }

    /**
     * The offers opened here cannot be told apart from the others.
     */
    public function down(): void
    {
        //
    }
};
