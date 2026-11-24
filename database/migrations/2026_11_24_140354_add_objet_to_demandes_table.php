<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A prestation DAG agrees with an entreprise outside the catalogue (its
     * contract generated from the « Contrats & clauses » page) is named by
     * its subject, written by the team.
     */
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->string('objet')->nullable()->after('formule_id');
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn('objet');
        });
    }
};
