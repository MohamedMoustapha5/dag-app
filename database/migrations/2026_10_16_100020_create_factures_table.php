<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->enum('type', ['SERVICE', 'ABONNEMENT', 'FORMATION'])->default('SERVICE');
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises_partenaires')->nullOnDelete();
            $table->foreignUuid('apprenant_id')->nullable()->constrained('apprenants')->nullOnDelete();
            $table->foreignUuid('demande_id')->nullable()->constrained('demandes')->nullOnDelete();
            $table->foreignUuid('abonnement_id')->nullable()->constrained('abonnements')->nullOnDelete();
            $table->foreignUuid('inscription_id')->nullable()->constrained('inscriptions')->nullOnDelete();
            $table->foreignUuid('contrat_id')->nullable()->constrained('contrats')->nullOnDelete();
            $table->decimal('montant', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->date('echeance')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'PAYEE', 'PARTIELLEMENT_PAYEE', 'EN_ECHEC'])->default('EN_ATTENTE');
            $table->enum('moyen_paiement', ['OM', 'MTN_MOMO', 'CARTE', 'ESPECES', 'VIREMENT', 'MANUEL'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
