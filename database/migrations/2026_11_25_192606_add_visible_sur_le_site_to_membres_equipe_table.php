<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether a team member appears in « L'équipe » on the home and À propos
     * pages. The administration account (equipe:envoyer-acces) is hidden.
     */
    public function up(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->boolean('visible_sur_le_site')->default(true)->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->dropColumn('visible_sur_le_site');
        });
    }
};
