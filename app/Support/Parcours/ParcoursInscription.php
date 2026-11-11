<?php

namespace App\Support\Parcours;

use App\Models\Inscription;
use Illuminate\Support\Carbon;

/**
 * Where an enrolment stands in its procedure, for the apprenant and for the
 * DAG team: dossier (apprenant) → examen du dossier (DAG) → frais
 * d'inscription (apprenant) → formation.
 */
class ParcoursInscription
{
    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const ETAPES = [
        'dossier' => ["Dossier d'inscription", Acteur::CLIENT],
        'examen' => ['Validation du dossier', Acteur::DAG],
        'paiement' => ["Frais d'inscription", Acteur::CLIENT],
        'formation' => ['Formation', Acteur::DAG],
    ];

    /**
     * @param  'client'|'admin'  $audience
     * @return array{
     *     etapes: list<array{cle: string, libelle: string, acteur: string, libelleActeur: string, etat: string}>,
     *     prochaine: array{cle: string, acteur: string, libelleActeur: string, aVous: bool, titre: string, texte: string, action: string|null, information: bool}|null,
     *     termine: bool,
     *     interrompu: string|null
     * }
     */
    public static function pour(Inscription $inscription, string $audience): array
    {
        $inscription->loadMissing(['facture', 'formationSession']);

        $statut = $inscription->statut;
        $faites = [
            'dossier' => $statut !== 'DOSSIER_A_COMPLETER',
            'examen' => in_array($statut, ['EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE'], true),
            // A fee already paid counts even while DAG still has to confirm the
            // seat (enrolments paid before dossiers existed).
            'paiement' => in_array($statut, ['VALIDEE', 'FORMEE'], true)
                || ($statut === 'EN_ATTENTE_PAIEMENT' && $inscription->facture?->statut === 'PAYEE'),
            'formation' => $statut === 'FORMEE',
        ];

        $courante = null;
        foreach ($faites as $cle => $faite) {
            if (! $faite) {
                $courante = $cle;

                break;
            }
        }

        $interrompu = $statut === 'ABANDONNEE'
            ? ($audience === 'client' ? "Cette inscription n'a pas été retenue." : 'Inscription refusée ou abandonnée.')
            : null;

        $etapes = [];
        foreach (self::ETAPES as $cle => [$libelle, $acteur]) {
            $etapes[] = [
                'cle' => $cle,
                'libelle' => $libelle,
                'acteur' => $acteur,
                'libelleActeur' => Acteur::libelle($acteur, $audience, 'Apprenant'),
                'etat' => $faites[$cle] ? 'faite' : ($cle === $courante && $interrompu === null ? 'en_cours' : 'a_venir'),
            ];
        }

        return [
            'etapes' => $etapes,
            'prochaine' => $courante === null || $interrompu !== null ? null : self::prochaine($inscription, $courante, $audience),
            'termine' => $courante === null,
            'interrompu' => $interrompu,
        ];
    }

    /**
     * @param  'client'|'admin'  $audience
     * @return array{cle: string, acteur: string, libelleActeur: string, aVous: bool, titre: string, texte: string, action: string|null, information: bool}
     */
    private static function prochaine(Inscription $inscription, string $etape, string $audience): array
    {
        $apprenant = $audience === 'client';
        $montant = $inscription->facture !== null
            ? number_format(max(0, (float) $inscription->facture->montant - (float) $inscription->facture->montant_paye), 0, ',', Acteur::ESPACE_INSECABLE).Acteur::ESPACE_INSECABLE.'FCFA'
            : null;
        $debut = $inscription->formationSession?->date_debut;
        $dateDebut = $debut !== null ? Carbon::parse($debut)->format('d/m/Y') : null;

        [$acteur, $titre, $texte, $action] = match ($etape) {
            'dossier' => [
                Acteur::CLIENT,
                $apprenant ? 'Compléter votre dossier' : 'Complément du dossier',
                $apprenant
                    ? "L'équipe DAG vous demande de corriger ou d'ajouter des pièces : déposez-les ci-dessous."
                    : "Un complément a été demandé : en attente des pièces de l'apprenant.",
                $apprenant ? 'completer_dossier' : null,
            ],
            'examen' => [
                Acteur::DAG,
                $apprenant ? 'Examen de votre dossier' : 'Examiner le dossier',
                $apprenant
                    ? "Votre dossier est en cours d'examen par l'équipe DAG. Vous serez prévenu dès sa validation."
                    : 'Vérifiez les pièces puis validez le dossier, demandez un complément ou refusez-le.',
                $apprenant ? null : 'examiner_dossier',
            ],
            'paiement' => [
                Acteur::CLIENT,
                $apprenant ? "Régler les frais d'inscription" : 'Paiement des frais',
                $apprenant
                    ? 'Votre dossier est validé. Réglez les frais d\'inscription'.($montant !== null ? " ({$montant})" : '').' pour confirmer votre place.'
                    : "Dossier validé : en attente du paiement des frais d'inscription".($montant !== null ? " ({$montant})" : '').'.',
                $apprenant ? 'payer' : null,
            ],
            'formation' => $inscription->statut === 'EN_ATTENTE_PAIEMENT' ? [
                Acteur::DAG,
                $apprenant ? 'Confirmation de votre place' : "Confirmer l'inscription",
                $apprenant
                    ? "Vos frais d'inscription sont réglés : l'équipe DAG confirme votre place et vous prévient."
                    : "Frais réglés : confirmez l'inscription de l'apprenant.",
                $apprenant ? null : 'confirmer_inscription',
            ] : [
                Acteur::DAG,
                $apprenant ? 'Place confirmée' : 'Formation à venir',
                $apprenant
                    ? 'Votre inscription est confirmée'.($dateDebut !== null ? " : rendez-vous le {$dateDebut}." : '.')
                    : 'Inscription confirmée'.($dateDebut !== null ? " : la session débute le {$dateDebut}." : '.'),
                null,
            ],
            default => [
                Acteur::DAG,
                $apprenant ? 'Place confirmée' : 'Formation à venir',
                $apprenant
                    ? 'Votre inscription est confirmée'.($dateDebut !== null ? " : rendez-vous le {$dateDebut}." : '.')
                    : 'Inscription confirmée'.($dateDebut !== null ? " : la session débute le {$dateDebut}." : '.'),
                null,
            ],
        };

        return [
            'cle' => $etape,
            'acteur' => $acteur,
            'libelleActeur' => Acteur::libelle($acteur, $audience, 'Apprenant'),
            'aVous' => Acteur::estPour($acteur, $audience),
            'titre' => $titre,
            'texte' => $texte,
            'action' => $action,
            // A confirmed seat needs nothing more from anyone until the session.
            'information' => $etape === 'formation' && $inscription->statut !== 'EN_ATTENTE_PAIEMENT',
        ];
    }
}
