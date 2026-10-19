<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->text('contenu');
            $table->json('services_inclus')->nullable();
            $table->string('prix_affiche')->default('Prix sur devis, négocié après connexion');
            $table->boolean('populaire')->default(false);
            $table->enum('statut', ['BROUILLON', 'PUBLIEE'])->default('PUBLIEE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formules');
    }
};
