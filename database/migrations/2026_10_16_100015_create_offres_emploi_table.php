<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres_emploi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entreprise_id')->constrained('entreprises_partenaires')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->enum('type_contrat', ['STAGE', 'CDD', 'CDI'])->default('STAGE');
            $table->string('duree')->nullable();
            $table->text('competences')->nullable();
            $table->string('ville')->nullable();
            $table->date('date_limite')->nullable();
            $table->enum('statut', ['BROUILLON', 'PUBLIEE', 'CLOTUREE'])->default('PUBLIEE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres_emploi');
    }
};
