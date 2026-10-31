<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row of the public grille tarifaire (indicative price list) — a
     * prestation under a numbered category, e.g. "1. Graphisme & identité
     * visuelle" → "Logo simple" → "25 000–60 000". `tarif` stays free text
     * since real entries mix ranges, "/ mois", "/ page" and "Sur devis".
     */
    public function up(): void
    {
        Schema::create('lignes_tarifaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('categorie');
            $table->unsignedSmallInteger('categorie_ordre');
            $table->string('prestation');
            $table->string('tarif');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lignes_tarifaires');
    }
};
