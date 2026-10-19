<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises_partenaires')->nullOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignUuid('formule_id')->nullable()->constrained('formules')->nullOnDelete();
            $table->text('description');
            $table->decimal('montant_negocie', 12, 2)->nullable();
            $table->enum('statut', ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE', 'PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE', 'REFUSEE'])->default('BROUILLON');
            $table->boolean('est_partenaire_nova_tech')->default(false);
            $table->enum('avancement_partenaire', ['A_TRANSMETTRE', 'TRANSMIS', 'DEVIS_RECU', 'EN_COURS', 'LIVRE'])->nullable();
            $table->dateTime('derniere_relance_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
