<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->timestamp('renouvele_le')->nullable()->after('echeance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropColumn('renouvele_le');
        });
    }
};
