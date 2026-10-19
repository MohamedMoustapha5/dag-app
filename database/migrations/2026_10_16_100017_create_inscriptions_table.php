<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignUuid('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignUuid('formation_session_id')->nullable()->constrained('formation_sessions')->nullOnDelete();
            $table->decimal('montant_negocie', 12, 2)->nullable();
            $table->enum('statut', ['BROUILLON', 'LISTE_ATTENTE', 'EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE', 'ABANDONNEE'])->default('BROUILLON');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
