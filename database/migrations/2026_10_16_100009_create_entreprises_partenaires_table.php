<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises_partenaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('secteur')->nullable();
            $table->string('adresse')->nullable();
            $table->string('rccm')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'ACTIF', 'INACTIF'])->default('EN_ATTENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises_partenaires');
    }
};
