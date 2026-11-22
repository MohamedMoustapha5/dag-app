<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A particulier does not sign a contract: they validate the devis
     * (proforma) online, which opens the payment. This records when.
     */
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->timestamp('devis_valide_le')->nullable()->after('message_devis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn('devis_valide_le');
        });
    }
};
