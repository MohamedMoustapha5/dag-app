<?php

use App\Support\Partenariats\ContratPartenariat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The one-line modèle seeded at the start, replaced by the full contract. */
    private const MODELE_INITIAL = 'Contrat-cadre de partenariat entre DAG et {{entreprise}}.';

    /**
     * A partnership between DAG and an entreprise or an organisation:
     * requested by the client or proposed by DAG, it is sealed by a
     * partnership contract the client signs and DAG validates.
     */
    public function up(): void
    {
        Schema::create('partenariats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('origine', ['DEMANDE', 'PROPOSITION']);
            $table->enum('statut', ['EN_ETUDE', 'REFUSE', 'CONTRAT', 'ACTIF', 'TERMINE', 'ANNULE'])->default('EN_ETUDE');
            $table->json('domaines');
            $table->text('message')->nullable();
            $table->unsignedTinyInteger('duree_mois')->nullable();
            $table->date('debut')->nullable();
            $table->date('fin')->nullable();
            $table->foreignUuid('contrat_id')->nullable()->constrained('contrats')->nullOnDelete();
            $table->text('motif_refus')->nullable();
            $table->foreignUuid('traite_par_id')->nullable()->constrained('membres_equipe')->nullOnDelete();
            $table->dateTime('traite_le')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'statut']);
        });

        // Without any modèle of this type, ContratPartenariat uses the same
        // default text: only the one-line modèle seeded at the start is replaced.
        DB::table('modeles_contrat')
            ->where('type', 'CONTRAT_PARTENAIRE')
            ->where('contenu', self::MODELE_INITIAL)
            ->update([
                'nom' => 'Contrat de partenariat',
                'contenu' => ContratPartenariat::MODELE_PAR_DEFAUT,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('partenariats');
    }
};
