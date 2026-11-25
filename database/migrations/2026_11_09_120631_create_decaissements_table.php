<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decaissements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('montant', 12, 2);
            $table->enum('moyen', ['OM', 'MTN_MOMO']);
            $table->string('telephone', 20);
            $table->string('beneficiaire');
            $table->string('motif');
            $table->enum('statut', ['EN_ATTENTE', 'CONFIRME', 'ECHEC'])->default('EN_ATTENTE');
            $table->string('reference_transaction')->nullable()->index();
            $table->string('motif_echec')->nullable();
            $table->foreignUuid('demande_par_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->dateTime('date_execution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decaissements');
    }
};
