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
        // The names go through bindings: no text is ever built into the SQL.
        DB::update('UPDATE modeles_contrat SET contenu = REPLACE(contenu, ?, ?) WHERE contenu LIKE ?', [$de, $vers, "%{$de}%"]);
        DB::update('UPDATE clauses SET contenu = REPLACE(contenu, ?, ?) WHERE contenu LIKE ?', [$de, $vers, "%{$de}%"]);

        DB::update(
            "UPDATE contrats SET contenu = REPLACE(contenu, ?, ?) WHERE statut IN ('BROUILLON', 'ENVOYE', 'REFUSE') AND date_signature IS NULL AND contenu LIKE ?",
            [$de, $vers, "%{$de}%"],
        );
    }
};
