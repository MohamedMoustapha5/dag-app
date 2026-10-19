<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('reference_transaction')->nullable();
            $table->enum('moyen', ['OM', 'MTN_MOMO', 'CARTE', 'ESPECES', 'VIREMENT', 'MANUEL']);
            $table->enum('statut', ['EN_ATTENTE', 'CONFIRME', 'ECHEC'])->default('EN_ATTENTE');
            $table->string('justificatif_path')->nullable();
            $table->foreignUuid('valide_par_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->dateTime('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
