<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance taken by the DAG team: a status richer than present/absent
 * (late, excused), a remark, and who took it and when. `present` stays the
 * single figure every progression reads (present or late).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->enum('statut', ['PRESENT', 'RETARD', 'ABSENT', 'EXCUSE'])->default('ABSENT')->after('present');
            $table->string('commentaire', 255)->nullable()->after('statut');
            $table->foreignUuid('pointee_par')->nullable()->after('commentaire')->constrained('membres_equipe')->nullOnDelete();
            $table->timestamp('pointee_at')->nullable()->after('pointee_par');
        });

        DB::table('presences')->where('present', true)->update(['statut' => 'PRESENT']);
    }

    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pointee_par');
            $table->dropColumn(['statut', 'commentaire', 'pointee_at']);
        });
    }
};
