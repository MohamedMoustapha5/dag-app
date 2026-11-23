<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Recruitment access is paid by subscription: an entreprise or an
     * organisation picks a forfait (1, 6 or 12 months, prices set by DAG),
     * pays its facture, and its offres and candidatures pages open for the
     * period. DAG can also offer a period. Each paid or offered period is
     * one row of acces_recrutement.
     */
    public function up(): void
    {
        Schema::create('forfaits_recrutement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('libelle');
            $table->unsignedSmallInteger('duree_mois');
            $table->decimal('prix', 12, 2);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('acces_recrutement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('forfait_id')->nullable()->constrained('forfaits_recrutement')->nullOnDelete();
            $table->string('libelle');
            $table->unsignedSmallInteger('duree_mois');
            $table->decimal('montant', 12, 2)->default(0);
            $table->enum('origine', ['PAIEMENT', 'OFFERT']);
            $table->enum('statut', ['EN_ATTENTE_PAIEMENT', 'ACTIF', 'ANNULE'])->default('EN_ATTENTE_PAIEMENT');
            $table->date('debut')->nullable();
            $table->date('fin')->nullable();
            $table->foreignUuid('offert_par_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'statut', 'fin']);
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->foreignUuid('acces_recrutement_id')->nullable()->after('contrat_id')
                ->constrained('acces_recrutement')->nullOnDelete();
        });

        $maintenant = now();

        DB::table('forfaits_recrutement')->insert([
            ['id' => (string) Str::uuid7(), 'code' => 'MENSUEL', 'libelle' => '1 mois', 'duree_mois' => 1, 'prix' => 10000, 'ordre' => 1, 'created_at' => $maintenant, 'updated_at' => $maintenant],
            ['id' => (string) Str::uuid7(), 'code' => 'SEMESTRIEL', 'libelle' => '6 mois', 'duree_mois' => 6, 'prix' => 55000, 'ordre' => 2, 'created_at' => $maintenant, 'updated_at' => $maintenant],
            ['id' => (string) Str::uuid7(), 'code' => 'ANNUEL', 'libelle' => '1 an', 'duree_mois' => 12, 'prix' => 80000, 'ordre' => 3, 'created_at' => $maintenant, 'updated_at' => $maintenant],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acces_recrutement_id');
        });

        Schema::dropIfExists('acces_recrutement');
        Schema::dropIfExists('forfaits_recrutement');
    }
};
