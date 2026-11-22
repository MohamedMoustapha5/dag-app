<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A particulier may decline the devis (proforma), with a reason: DAG
     * then sends a revised devis, or the demande is closed if the client
     * does not wish to go on.
     */
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->timestamp('devis_refuse_le')->nullable()->after('devis_valide_le');
            $table->string('motif_refus_devis', 1200)->nullable()->after('devis_refuse_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn(['devis_refuse_le', 'motif_refus_devis']);
        });
    }
};
