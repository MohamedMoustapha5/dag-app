<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Links a self-registered Entreprise client to its partner record once
     * DAG validates it as an official recruitment partner. Nullable because
     * most existing partners are created directly by DAG staff, with no
     * client account at all (section 6.6 of the cahier des charges).
     */
    public function up(): void
    {
        Schema::table('entreprises_partenaires', function (Blueprint $table) {
            $table->foreignUuid('client_id')->nullable()->unique()->after('id')
                ->constrained('clients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises_partenaires', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
