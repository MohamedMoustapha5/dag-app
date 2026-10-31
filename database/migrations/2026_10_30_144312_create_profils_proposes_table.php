<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * A candidate profile DAG staff suggests directly to a partner
     * entreprise, outside any specific offre_emploi (unlike `candidatures`,
     * which are always an apprenant applying to a published offer).
     */
    public function up(): void
    {
        Schema::create('profils_proposes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entreprise_id')->constrained('entreprises_partenaires')->cascadeOnDelete();
            $table->foreignUuid('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignUuid('membre_equipe_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->text('descriptif')->nullable();
            $table->enum('statut', ['PROPOSE', 'RETENU', 'REFUSE'])->default('PROPOSE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profils_proposes');
    }
};
