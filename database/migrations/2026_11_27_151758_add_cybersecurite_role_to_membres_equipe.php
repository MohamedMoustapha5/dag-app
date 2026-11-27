<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the Cybersécurité role to the team accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->enum('role', ['PROMOTRICE', 'JURISTE', 'COMMUNICATION_MARKETING', 'CONTROLE_GESTION', 'CREATION_CONTENU', 'DEVELOPPEUR', 'CYBERSECURITE'])->change();
        });
    }

    /**
     * Only possible while nobody holds the role: a member's role is never
     * changed behind the team's back.
     */
    public function down(): void
    {
        if (DB::table('membres_equipe')->where('role', 'CYBERSECURITE')->exists()) {
            throw new RuntimeException('Des membres ont le rôle Cybersécurité : changez-leur de rôle avant d\'annuler cette migration.');
        }

        DB::table('droits_roles')->where('role', 'CYBERSECURITE')->delete();

        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->enum('role', ['PROMOTRICE', 'JURISTE', 'COMMUNICATION_MARKETING', 'CONTROLE_GESTION', 'CREATION_CONTENU', 'DEVELOPPEUR'])->change();
        });
    }
};
