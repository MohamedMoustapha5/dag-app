<?php

use Database\Seeders\ContratDagSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The juriste supplied the legal basis of each article of the DAG
     * contract, and the contract adapted to subscriptions. Where the
     * previous version of the DAG contract is installed, as is, it is
     * replaced by the new one; a clause library the team has changed is
     * left alone, and so are the contracts already drafted.
     */
    public function up(): void
    {
        $titres = DB::table('clauses')->orderBy('ordre')->orderBy('created_at')->pluck('titre')->all();

        if ($titres === ContratDagSeeder::TITRES_SANS_BASE_LEGALE) {
            (new ContratDagSeeder)->run();
        }
    }

    /**
     * The previous texts are not kept.
     */
    public function down(): void
    {
        //
    }
};
