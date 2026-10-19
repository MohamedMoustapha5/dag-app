<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->constrained('entreprises_partenaires')->cascadeOnDelete();
            $table->foreignUuid('formule_id')->constrained('formules')->cascadeOnDelete();
            $table->decimal('montant_negocie', 12, 2);
            $table->date('date_souscription');
            $table->date('cycle_debut')->nullable();
            $table->date('echeance')->nullable();
            $table->enum('statut', ['BROUILLON', 'ACTIF', 'SUSPENDU', 'TERMINE'])->default('BROUILLON');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
