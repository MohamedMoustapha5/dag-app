<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->string('ville');
            $table->enum('mode', ['PRESENTIEL', 'EN_LIGNE', 'LES_DEUX'])->default('PRESENTIEL');
            $table->string('lieu')->nullable();
            $table->string('formateur')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->smallInteger('places')->default(15);
            $table->string('image')->nullable();
            $table->enum('statut', ['OUVERTE', 'COMPLETE', 'BROUILLON', 'TERMINEE'])->default('OUVERTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_sessions');
    }
};
