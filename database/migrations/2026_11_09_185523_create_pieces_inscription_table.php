<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The documents an apprenant joins to an enrolment (copy of the ID card,
 * birth certificate…), one row per required piece, reviewed by DAG before
 * the registration fee is billed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_inscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->string('libelle');
            $table->string('chemin');
            $table->string('nom_original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_inscription');
    }
};
