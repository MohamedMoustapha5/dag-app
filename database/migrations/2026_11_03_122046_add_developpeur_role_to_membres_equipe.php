<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->enum('role', ['PROMOTRICE', 'JURISTE', 'COMMUNICATION_MARKETING', 'CONTROLE_GESTION', 'CREATION_CONTENU', 'DEVELOPPEUR'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->enum('role', ['PROMOTRICE', 'JURISTE', 'COMMUNICATION_MARKETING', 'CONTROLE_GESTION', 'CREATION_CONTENU'])->change();
        });
    }
};
