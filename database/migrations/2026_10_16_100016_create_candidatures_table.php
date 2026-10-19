<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignUuid('offre_id')->constrained('offres_emploi')->cascadeOnDelete();
            $table->enum('statut', ['DEPOSEE', 'EN_ETUDE', 'RETENUE', 'REFUSEE', 'PLACEE'])->default('DEPOSEE');
            $table->timestamps();
            $table->unique(['apprenant_id', 'offre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
