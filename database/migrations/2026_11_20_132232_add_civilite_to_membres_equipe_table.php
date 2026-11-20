<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the courtesy title (Mr / Mme) shown before each team member's
     * name, fills it in for the founding team, and corrects two names:
     * the directrice is Mandou and the secrétaire is Fadimatou.
     * A name is only changed while it still holds the old seeded value.
     */
    public function up(): void
    {
        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->enum('civilite', ['M', 'MME'])->nullable()->after('name');
        });

        DB::table('membres_equipe')->where('email', 'afsamandou15@gmail.com')->where('name', 'Afsa Maira')
            ->update(['name' => 'Mandou']);

        DB::table('membres_equipe')->where('role', 'CONTROLE_GESTION')->where('name', 'Layana D.')
            ->update(['name' => 'Fadimatou']);

        DB::table('membres_equipe')->whereIn('name', ['Mandou', 'Fadimatou'])->update(['civilite' => 'MME']);

        DB::table('membres_equipe')->whereIn('name', ['Pascal', 'Senghor', 'Hussein', 'Mustapha'])->update(['civilite' => 'M']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('membres_equipe')->where('email', 'afsamandou15@gmail.com')->where('name', 'Mandou')
            ->update(['name' => 'Afsa Maira']);

        DB::table('membres_equipe')->where('role', 'CONTROLE_GESTION')->where('name', 'Fadimatou')
            ->update(['name' => 'Layana D.']);

        Schema::table('membres_equipe', function (Blueprint $table) {
            $table->dropColumn('civilite');
        });
    }
};
