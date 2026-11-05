<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registration fee of a session, in FCFA: shown to the apprenant and
     * billed when they enrol. Null until DAG sets it, and a session without
     * a fee cannot take enrolments.
     */
    public function up(): void
    {
        Schema::table('formation_sessions', function (Blueprint $table) {
            $table->decimal('prix', 12, 2)->nullable()->after('places');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formation_sessions', function (Blueprint $table) {
            $table->dropColumn('prix');
        });
    }
};
