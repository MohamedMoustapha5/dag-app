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
        Schema::table('candidatures_spontanees', function (Blueprint $table) {
            $table->foreignUuid('apprenant_id')->nullable()->after('cv_path')
                ->constrained('apprenants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatures_spontanees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apprenant_id');
        });
    }
};
