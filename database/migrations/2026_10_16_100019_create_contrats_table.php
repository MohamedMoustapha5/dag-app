<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->enum('type', ['PRESTATION_SERVICE', 'ABONNEMENT', 'CONVENTION_STAGE', 'CONTRAT_PARTENAIRE']);
            $table->foreignUuid('modele_contrat_id')->nullable()->constrained('modeles_contrat')->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('apprenant_id')->nullable()->constrained('apprenants')->nullOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises_partenaires')->nullOnDelete();
            $table->foreignUuid('demande_id')->nullable()->constrained('demandes')->nullOnDelete();
            $table->foreignUuid('abonnement_id')->nullable()->constrained('abonnements')->nullOnDelete();
            $table->foreignUuid('candidature_id')->nullable()->constrained('candidatures')->nullOnDelete();
            $table->longText('contenu')->nullable();
            $table->decimal('montant', 12, 2)->nullable();
            $table->string('code_verification');
            $table->enum('statut', ['BROUILLON', 'ENVOYE', 'SIGNE', 'ACTIF', 'TERMINE', 'REFUSE', 'RESILIE'])->default('BROUILLON');
            $table->string('fichier_pdf')->nullable();
            $table->string('photo_signature')->nullable();
            $table->string('signataire_nom')->nullable();
            $table->string('ip_signature')->nullable();
            $table->dateTime('date_signature')->nullable();
            $table->boolean('signature_contestee')->default(false);
            $table->dateTime('signature_invalidee_at')->nullable();
            $table->dateTime('derniere_relance_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
