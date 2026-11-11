<?php

namespace App\Support\Parcours;

use App\Models\Demande;

/**
 * Where a service or abonnement demande stands in its procedure, for the
 * client and for the DAG team: every step, who acts on it, the current one,
 * and what has to be done next, worded for whoever is looking.
 *
 * Steps: demande → devis (DAG) → signature of the contract (client) →
 * validation of the contract (DAG) → payment (client) → prestation (DAG)
 * → clôture (DAG).
 */
class ParcoursDemande
{
    /**
     * @param  'client'|'admin'  $audience
     * @return array{
     *     etapes: list<array{cle: string, libelle: string, acteur: string, libelleActeur: string, etat: string}>,
     *     prochaine: array{cle: string, acteur: string, libelleActeur: string, aVous: bool, titre: string, texte: string, action: string|null, information: bool}|null,
     *     termine: bool,
     *     interrompu: string|null
     * }
     */
    public static function pour(Demande $demande, string $audience): array
    {
        $demande->loadMissing(['contrat', 'facture']);

        $faites = self::etapesFaites($demande);

        // The procedure only moves forward: once a later step is reached, the
        // earlier ones are behind it (a demande handled before a step existed,
        // e.g. paid before contracts were validated, is not sent back to it).
        $derniere = null;
        foreach (array_keys($faites) as $index => $cle) {
            if ($faites[$cle]) {
                $derniere = $index;
            }
        }
        foreach (array_keys($faites) as $index => $cle) {
            $faites[$cle] = $derniere !== null && $index <= $derniere;
        }

        $courante = null;
        foreach (array_keys($faites) as $cle) {
            if (! $faites[$cle]) {
                $courante = $cle;

                break;
            }
        }

        $interrompu = $demande->statut === 'REFUSEE'
            ? ($audience === 'client' ? "Votre demande n'a pas pu être retenue. Contactez l'équipe DAG pour en savoir plus." : 'Demande refusée : la procédure est arrêtée.')
            : null;

        $etapes = [];
        foreach (self::ETAPES as $cle => [$libelle, $acteur]) {
            $etapes[] = [
                'cle' => $cle,
                'libelle' => $libelle,
                'acteur' => $acteur,
                'libelleActeur' => Acteur::libelle($acteur, $audience),
                'etat' => $faites[$cle] ? 'faite' : ($cle === $courante && $interrompu === null ? 'en_cours' : 'a_venir'),
            ];
        }

        return [
            'etapes' => $etapes,
            'prochaine' => $courante === null || $interrompu !== null ? null : self::prochaine($demande, self::etapeAFaire($demande, $courante), $audience),
            'termine' => $courante === null,
            'interrompu' => $interrompu,
        ];
    }

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const ETAPES = [
        'demande' => ['Demande envoyée', Acteur::CLIENT],
        'devis' => ['Devis et contrat', Acteur::DAG],
        'signature' => ['Signature du contrat', Acteur::CLIENT],
        'validation' => ['Validation du contrat', Acteur::DAG],
        'paiement' => ['Paiement', Acteur::CLIENT],
        'prestation' => ['Réalisation', Acteur::DAG],
        'cloture' => ['Clôture', Acteur::DAG],
    ];

    /**
     * What actually has to be done at the current step. A demande paid
     * before its contract was signed (earlier procedure) cannot start
     * without it: getting the contract signed comes first.
     */
    private static function etapeAFaire(Demande $demande, string $courante): string
    {
        return $courante === 'prestation' && ! $demande->contratEstSigne() ? 'signature' : $courante;
    }

    /**
     * @return array<string, bool>
     */
    private static function etapesFaites(Demande $demande): array
    {
        $facturePayee = $demande->facture !== null && $demande->facture->statut === 'PAYEE';

        return [
            'demande' => true,
            'devis' => $demande->aAtteint('DEVIS_ENVOYE'),
            'signature' => $demande->contratEstSigne(),
            'validation' => $demande->contratEstValide(),
            'paiement' => $facturePayee || $demande->aAtteint('PAYEE'),
            'prestation' => $demande->aAtteint('LIVREE'),
            'cloture' => $demande->statut === 'TERMINEE',
        ];
    }

    /**
     * @param  'client'|'admin'  $audience
     * @return array{cle: string, acteur: string, libelleActeur: string, aVous: bool, titre: string, texte: string, action: string|null, information: bool}
     */
    private static function prochaine(Demande $demande, string $etape, string $audience): array
    {
        $client = $audience === 'client';
        $contrat = $demande->contrat;
        $solde = $demande->facture !== null
            ? number_format(max(0, (float) $demande->facture->montant - (float) $demande->facture->montant_paye), 0, ',', Acteur::ESPACE_INSECABLE).Acteur::ESPACE_INSECABLE.'FCFA'
            : null;

        [$acteur, $titre, $texte, $action] = match ($etape) {
            'devis' => [
                Acteur::DAG,
                $client ? 'Étude de votre demande' : 'Envoyer le devis',
                $client
                    ? "L'équipe DAG étudie votre demande. Vous recevrez ici le devis et le contrat à signer."
                    : 'Étudiez la demande puis envoyez le devis : le contrat à signer part avec.',
                $client ? null : 'envoyer_devis',
            ],
            'signature' => match (true) {
                $contrat !== null && $contrat->statut === 'ENVOYE' => [
                    Acteur::CLIENT,
                    $client ? 'Signer votre contrat' : 'Signature du client',
                    $client
                        ? 'Lisez le contrat qui accompagne votre devis, puis signez-le en quelques minutes.'
                        : 'Le contrat est envoyé : en attente de la signature du client.',
                    $client ? 'signer_contrat' : 'ouvrir_contrat',
                ],
                $contrat !== null && $contrat->statut === 'BROUILLON' && $contrat->motif_rectification !== null => [
                    Acteur::DAG,
                    $client ? 'Correction de votre contrat' : 'Corriger le contrat',
                    $client
                        ? 'Vous avez demandé une rectification : DAG corrige le contrat et vous le renvoie pour signature.'
                        : 'Le client a demandé une rectification : corrigez le contrat et renvoyez-le.',
                    $client ? null : 'ouvrir_contrat',
                ],
                $contrat !== null && $contrat->statut === 'REFUSE' => [
                    Acteur::DAG,
                    $client ? 'Révision du contrat' : 'Reprendre le contrat',
                    $client
                        ? 'DAG revoit votre contrat et vous en renvoie une version.'
                        : 'Le contrat a été refusé : reprenez-le pour le corriger et le renvoyer.',
                    $client ? null : 'ouvrir_contrat',
                ],
                default => [
                    Acteur::DAG,
                    $client ? 'Préparation du contrat' : 'Envoyer le contrat',
                    $client
                        ? 'DAG finalise votre contrat : vous le recevrez ici pour signature.'
                        : "Le contrat n'est pas encore envoyé au client : relisez-le et envoyez-le.",
                    $client ? null : ($contrat !== null ? 'ouvrir_contrat' : 'generer_contrat'),
                ],
            },
            'validation' => [
                Acteur::DAG,
                $client ? 'Validation de votre contrat' : 'Valider le contrat',
                $client
                    ? "Merci, votre contrat est signé. L'équipe DAG le vérifie et le valide : vous serez prévenu dès que le paiement est ouvert."
                    : 'Le client a signé : vérifiez le contrat et validez-le pour lui ouvrir le paiement.',
                $client ? null : 'ouvrir_contrat',
            ],
            'paiement' => [
                Acteur::CLIENT,
                $client ? 'Régler votre facture' : 'Paiement du client',
                $client
                    ? 'Votre contrat est validé. Réglez la facture'.($solde !== null ? " ({$solde})" : '').' pour lancer la prestation.'
                    : 'Contrat validé : en attente du paiement du client'.($solde !== null ? " ({$solde} restant)" : '').'.',
                $client ? 'payer' : null,
            ],
            'prestation' => [
                Acteur::DAG,
                $client ? 'Réalisation de la prestation' : ($demande->statut === 'EN_COURS' ? 'Livrer la prestation' : 'Démarrer la prestation'),
                $client
                    ? "Paiement reçu : l'équipe DAG réalise votre prestation. Vous suivez son avancement ici."
                    : ($demande->statut === 'EN_COURS'
                        ? 'Prestation en cours : passez la demande à « Livrée » une fois le travail remis au client.'
                        : 'Paiement reçu : passez la demande à « En cours » au démarrage de la prestation.'),
                $client ? null : 'changer_statut',
            ],
            default => [
                Acteur::DAG,
                $client ? 'Clôture de la demande' : 'Clôturer la demande',
                $client
                    ? "Votre prestation est livrée. L'équipe DAG clôture la demande."
                    : 'Prestation livrée : passez la demande à « Terminée ».',
                $client ? null : 'changer_statut',
            ],
        };

        return [
            'cle' => $etape,
            'acteur' => $acteur,
            'libelleActeur' => Acteur::libelle($acteur, $audience),
            'aVous' => Acteur::estPour($acteur, $audience),
            'titre' => $titre,
            'texte' => $texte,
            'action' => $action,
            'information' => false,
        ];
    }
}
