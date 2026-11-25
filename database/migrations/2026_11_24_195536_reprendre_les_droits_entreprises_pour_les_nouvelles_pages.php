<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The rights lines that now each gate their own page. */
    private const NOUVELLES_DONNEES = ['PARTENARIATS', 'LOGOS_PARTENAIRES', 'ABONNEMENTS_RECRUTEMENT'];

    /**
     * The partnerships, the partner logos and the recruitment subscriptions
     * had followed the « Entreprises partenaires & offres » rights line;
     * they now have their own. A level the team had set on that line for a
     * role is carried over to the three new ones, so nobody loses or gains
     * access when this runs.
     */
    public function up(): void
    {
        $surcharges = DB::table('droits_roles')->where('donnee', 'ENTREPRISES_OFFRES')->get();

        foreach ($surcharges as $surcharge) {
            foreach (self::NOUVELLES_DONNEES as $donnee) {
                DB::table('droits_roles')->updateOrInsert(
                    ['role' => $surcharge->role, 'donnee' => $donnee],
                    ['niveau' => $surcharge->niveau, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('droits_roles')->whereIn('donnee', self::NOUVELLES_DONNEES)->delete();
    }
};
