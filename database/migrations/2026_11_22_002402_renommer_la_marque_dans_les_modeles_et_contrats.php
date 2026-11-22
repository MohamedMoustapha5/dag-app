<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ANCIEN = 'DAG — Digital Agency Group';

    private const NOUVEAU = 'Digital Agency Group';

    /**
     * The agency now signs as « Digital Agency Group ». The contract
     * modèles and clauses are updated, and so are contracts not signed yet;
     * a signed contract keeps the text the client agreed to.
     */
    public function up(): void
    {
        $this->remplacer(self::ANCIEN, self::NOUVEAU);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->remplacer(self::NOUVEAU, self::ANCIEN);
    }

    private function remplacer(string $de, string $vers): void
    {
        foreach (['modeles_contrat', 'clauses'] as $table) {
            DB::table($table)
                ->where('contenu', 'like', '%'.$de.'%')
                ->update(['contenu' => DB::raw('REPLACE(contenu, '.DB::getPdo()->quote($de).', '.DB::getPdo()->quote($vers).')')]);
        }

        DB::table('contrats')
            ->whereIn('statut', ['BROUILLON', 'ENVOYE', 'REFUSE'])
            ->whereNull('date_signature')
            ->where('contenu', 'like', '%'.$de.'%')
            ->update(['contenu' => DB::raw('REPLACE(contenu, '.DB::getPdo()->quote($de).', '.DB::getPdo()->quote($vers).')')]);
    }
};
