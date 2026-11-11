<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The client now signs the contract before paying: "Contrat signé" sits
 * between "Devis envoyé" and "Payée".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->enum('statut', ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE', 'CONTRAT_SIGNE', 'PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE', 'REFUSEE'])
                ->default('BROUILLON')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('demandes')->where('statut', 'CONTRAT_SIGNE')->update(['statut' => 'DEVIS_ENVOYE']);

        Schema::table('demandes', function (Blueprint $table) {
            $table->enum('statut', ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE', 'PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE', 'REFUSEE'])
                ->default('BROUILLON')
                ->change();
        });
    }
};
