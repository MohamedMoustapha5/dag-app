<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidatures', function (Blueprint $table) {
            $table->foreignUuid('deposee_par_id')->nullable()->after('statut')
                ->constrained('membres_equipe')->nullOnDelete();
        });

        Schema::table('attestations', function (Blueprint $table) {
            $table->foreignUuid('valide_par_id')->nullable()->after('date_emission')
                ->constrained('membres_equipe')->nullOnDelete();
            $table->string('fichier_pdf')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('valide_par_id');
        });

        Schema::table('candidatures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deposee_par_id');
        });
    }
};
