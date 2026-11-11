<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An enrolment now starts with a dossier the apprenant submits (DOSSIER_EN_ETUDE),
 * which DAG validates (the fee is then billed: EN_ATTENTE_PAIEMENT), sends
 * back for completion (DOSSIER_A_COMPLETER) or refuses. Each formation lists
 * the pieces its dossier requires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->enum('statut', ['BROUILLON', 'DOSSIER_EN_ETUDE', 'DOSSIER_A_COMPLETER', 'LISTE_ATTENTE', 'EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE', 'ABANDONNEE'])
                ->default('BROUILLON')
                ->change();
            $table->text('motif_decision')->nullable()->after('statut');
            $table->dateTime('dossier_valide_at')->nullable()->after('motif_decision');
        });

        Schema::table('formations', function (Blueprint $table) {
            $table->json('pieces_requises')->nullable()->after('prerequis');
        });
    }

    public function down(): void
    {
        DB::table('inscriptions')->whereIn('statut', ['DOSSIER_EN_ETUDE', 'DOSSIER_A_COMPLETER'])->update(['statut' => 'BROUILLON']);

        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropColumn(['motif_decision', 'dossier_valide_at']);
            $table->enum('statut', ['BROUILLON', 'LISTE_ATTENTE', 'EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE', 'ABANDONNEE'])
                ->default('BROUILLON')
                ->change();
        });

        Schema::table('formations', function (Blueprint $table) {
            $table->dropColumn('pieces_requises');
        });
    }
};
