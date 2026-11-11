<?php

namespace App\Support;

use App\Models\Clause;
use App\Models\Contrat;
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

    /**
     * The contract type of a demande: a formule is a subscription, anything
     * else a one-off prestation.
     */
    public static function typePour(Demande $demande): string
    {
        return $demande->formule_id !== null ? 'ABONNEMENT' : 'PRESTATION_SERVICE';
    }

    /**
     * Creates the draft contract of a demande (invisible to the client until
     * DAG sends it). Without an explicit modèle, the active one of the
     * contract type is used, falling back to the prestation one.
     */
    public static function brouillonPour(Demande $demande, ?ModeleContrat $modele = null): Contrat
    {
        $type = self::typePour($demande);
        $modele ??= self::modeleParDefaut($type) ?? self::modeleParDefaut('PRESTATION_SERVICE');

        return Contrat::create([
            'type' => $type,
            'modele_contrat_id' => $modele?->id,
            'client_id' => $demande->client_id,
            'entreprise_id' => $demande->entreprise_id,
            'demande_id' => $demande->id,
            'contenu' => self::pour($demande, $modele),
            'montant' => $demande->montant_negocie,
            'statut' => 'BROUILLON',
        ]);
    }

    /**
     * Sends the contract the client signs before paying, together with the
     * devis: drafted on the first devis, and redrafted at the new amount when
     * an unsigned devis is re-sent. A signed contract is never touched, since
     * it is the evidence of what the client agreed to.
     */
    public static function envoyerAvecDevis(Demande $demande): Contrat
    {
        $demande->load('contrat');
        $contrat = $demande->contrat;

        if ($contrat === null) {
            $contrat = self::brouillonPour($demande);
        } elseif (! $contrat->estSigne()) {
            // A new devis is a new contract: its rectification allowance starts over.
            $contrat->update([
                'contenu' => self::pour($demande, $contrat->modeleContrat),
                'montant' => $demande->montant_negocie,
                'nombre_rectifications' => 0,
                'motif_rectification' => null,
                'rectification_demandee_at' => null,
            ]);
        }

        if (in_array($contrat->statut, ['BROUILLON', 'REFUSE'], true)) {
            $contrat->update(['statut' => 'ENVOYE']);
        }

        return $contrat;
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

        // The same placeholders work in the modèle and in the clauses, so an
        // article such as "Objet de la demande" can quote the client's request.
        $valeurs = [
            '{{client}}' => $nomClient,
            '{{service}}' => $demande->libelleObjet(),
            '{{formule}}' => $demande->formule_id !== null ? $demande->formule->nom : $demande->libelleObjet(),
            '{{description}}' => trim((string) $demande->description),
            '{{montant}}' => $montant,
            '{{date}}' => now()->format('d/m/Y'),
            '{{reference}}' => $demande->reference,
        ];

        $texte = strtr($modele->contenu ?? '', $valeurs);

        $articles = Clause::query()
            ->orderBy('ordre')
            ->get()
            ->values()
            ->map(fn (Clause $clause, int $index) => ($index + 1).'. '.$clause->titre."\n".strtr($clause->contenu, $valeurs))
            ->implode("\n\n");

        return trim($texte."\n\n".$articles);
    }

    /**
     * Placeholders a modèle or a clause can use, shown to the team writing them.
     *
     * @return array<string, string>
     */
    public static function variables(): array
    {
        return [
            '{{client}}' => 'Nom du client',
            '{{service}}' => 'Service ou formule demandé',
            '{{formule}}' => 'Nom de la formule (abonnement)',
            '{{description}}' => 'Description de la demande du client',
            '{{montant}}' => 'Montant du devis (FCFA)',
            '{{reference}}' => 'Référence de la demande',
            '{{date}}' => 'Date de rédaction',
        ];
    }
}
