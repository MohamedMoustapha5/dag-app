<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_contrat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->enum('type', ['PRESTATION_SERVICE', 'ABONNEMENT', 'CONVENTION_STAGE', 'CONTRAT_PARTENAIRE']);
            $table->longText('contenu');
            $table->enum('statut', ['BROUILLON', 'ACTIF'])->default('ACTIF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_contrat');
    }
};
