<?php

namespace App\Support;

use App\Models\Clause;
use App\Models\Demande;
use App\Models\ModeleContrat;

/**
 * Drafts the text of a prestation contract from a modèle and the clause
 * library: the modèle's placeholders are filled from the demande, then the
 * clauses follow as numbered articles ("1. Titre", paragraph), which is the
 * layout ContratPdf turns into titled sections.
 */
class ContratRedaction
{
    /**
     * The active modèle used when none is chosen explicitly.
     */
    public static function modeleParDefaut(string $type): ?ModeleContrat
    {
        return ModeleContrat::query()
            ->where('type', $type)
            ->where('statut', 'ACTIF')
            ->latest()
            ->first();
    }

    public static function pour(Demande $demande, ?ModeleContrat $modele): string
    {
        $demande->loadMissing(['client', 'entreprise', 'service', 'formule']);

        $nomClient = $demande->client_id !== null
            ? $demande->client->nom
            : ($demande->entreprise_id !== null ? $demande->entreprise->nom : '—');

        $montant = $demande->montant_negocie !== null
            ? number_format((float) $demande->montant_negocie, 0, ',', ' ')
            : '—';

        $texte = strtr($modele->contenu ?? '', [
            '{{client}}' => $nomClient,
            '{{service}}' => $demande->libelleObjet(),
            '{{montant}}' => $montant,
            '{{date}}' => now()->format('d/m/Y'),
            '{{reference}}' => $demande->reference,
        ]);

        $articles = Clause::query()
            ->orderBy('ordre')
            ->get()
            ->values()
            ->map(fn (Clause $clause, int $index) => ($index + 1).'. '.$clause->titre."\n".$clause->contenu)
            ->implode("\n\n");

        return trim($texte."\n\n".$articles);
    }
}
