<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The provider's payment page of an online attempt (Fapshi, Moneroo):
     * a payer who left it without paying is sent back to the same page
     * instead of opening a second payment for the same facture.
     */
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->string('lien_paiement', 1000)->nullable()->after('reference_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn('lien_paiement');
        });
    }
};
