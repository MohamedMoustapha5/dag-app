<?php

use App\Support\Partenariats\ContratPartenariat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The partnership contract written by DAG's lawyer names the partner's
     * legal form, NIU, and the title of its representative: the partner
     * gives them when signing, and they are kept with the signature.
     *
     * The partnership modèle generated before this text was supplied is
     * replaced by it; a modèle the team has rewritten is left alone.
     */
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('forme_juridique')->nullable()->after('quartier_prestation');
            $table->string('niu', 30)->nullable()->after('forme_juridique');
            $table->string('qualite_signataire')->nullable()->after('niu');
        });

        DB::table('modeles_contrat')
            ->where('type', 'CONTRAT_PARTENAIRE')
            ->where('contenu', 'like', 'Entre Digital Agency Group, ci-après « DAG », d\'une part%')
            ->update([
                'contenu' => ContratPartenariat::MODELE_PAR_DEFAUT,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['forme_juridique', 'niu', 'qualite_signataire']);
        });
    }
};
