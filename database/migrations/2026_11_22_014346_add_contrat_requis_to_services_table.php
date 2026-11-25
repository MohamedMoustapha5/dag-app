<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether an entreprise or an organisation must sign a contract before
     * paying for this service. Without it, the devis validated online is
     * enough, as for a particulier. A new service does not require one
     * until DAG ticks it; the existing services keep the contract they
     * required so far.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('contrat_requis')->default(false)->after('prix');
        });

        DB::table('services')->update(['contrat_requis' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('contrat_requis');
        });
    }
};
