<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Path, on the private `local` disk, of the facture PDF generated on its
     * first download and served as is afterwards (contrats and attestations
     * already have this column).
     */
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->string('fichier_pdf')->nullable()->after('moyen_paiement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('fichier_pdf');
        });
    }
};
