<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Messages sent through the public contact form were stored but never
     * shown to the team: they become support tickets (origine PUBLIC) so
     * they land in the admin support inbox. The contact_messages table is
     * left in place, untouched, and no longer written to.
     */
    public function up(): void
    {
        $sequences = [];

        DB::table('contact_messages')->orderBy('created_at')->get()->each(function (object $contact) use (&$sequences): void {
            $date = Carbon::parse($contact->created_at ?? now());
            $annee = $date->year;
            $sequences[$annee] ??= DB::table('tickets_support')->whereYear('created_at', $annee)->count();
            $sequences[$annee]++;

            $ticketId = (string) Str::uuid();

            DB::table('tickets_support')->insert([
                'id' => $ticketId,
                'reference' => sprintf('DAG-S-%d-%04d', $annee, $sequences[$annee]),
                'user_id' => null,
                'origine' => 'PUBLIC',
                'nom' => $contact->nom,
                'email' => $contact->email,
                'categorie' => 'AUTRE',
                'sujet' => $contact->sujet,
                'statut' => $contact->statut === 'TRAITE' ? 'CLOS' : 'OUVERT',
                'dernier_message_at' => $date,
                'created_at' => $date,
                'updated_at' => $contact->updated_at ?? $date,
            ]);

            DB::table('messages_support')->insert([
                'id' => (string) Str::uuid(),
                'ticket_support_id' => $ticketId,
                'auteur' => 'DEMANDEUR',
                'membre_equipe_id' => null,
                'contenu' => $contact->message,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        });
    }

    /**
     * The copied tickets are removed; contact_messages still holds the originals.
     */
    public function down(): void
    {
        DB::table('tickets_support')->where('origine', 'PUBLIC')->delete();
    }
};
