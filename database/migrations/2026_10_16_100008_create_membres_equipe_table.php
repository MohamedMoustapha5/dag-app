<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres_equipe', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['PROMOTRICE', 'JURISTE', 'COMMUNICATION_MARKETING', 'CONTROLE_GESTION', 'CREATION_CONTENU']);
            $table->enum('statut', ['ACTIF', 'INACTIF'])->default('ACTIF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_equipe');
    }
};
