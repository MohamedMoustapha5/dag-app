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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notif_devis')->default(true)->after('locale');
            $table->boolean('notif_facture')->default(true)->after('notif_devis');
            $table->boolean('notif_offres')->default(false)->after('notif_facture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notif_devis', 'notif_facture', 'notif_offres']);
        });
    }
};
