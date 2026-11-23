<?php

namespace App\Support\Partenariats;

use App\Models\Partenariat;

/**
 * Where a partnership stands, in the shape the SuiviProcedure component
 * shows: each step with who acts on it, then the next step. The client and
 * the team see the same steps, each addressed in its own words.
 */
class ParcoursPartenariat
{
    /**
     * @return array{etapes: list<array<string, string>>, prochaine: array<string, mixed>|null, termine: bool, interrompu: string|null}
     */
    public static function pour(Partenariat $partenariat, bool $vueClient): array
    {
        $etape = $partenariat->etape();

        $definition = $partenariat->origine === 'PROPOSITION'
            ? [
                ['cle' => 'proposition', 'libelle' => 'Proposition de DAG', 'acteur' => 'DAG'],
                ['cle' => 'signature', 'libelle' => 'Signature du contrat', 'acteur' => 'CLIENT'],
                ['cle' => 'validation', 'libelle' => 'Validation par DAG', 'acteur' => 'DAG'],
            ]
            : [
                ['cle' => 'demande', 'libelle' => 'Demande de partenariat', 'acteur' => 'CLIENT'],
                ['cle' => 'etude', 'libelle' => 'Étude par DAG', 'acteur' => 'DAG'],
                ['cle' => 'signature', 'libelle' => 'Signature du contrat', 'acteur' => 'CLIENT'],
                ['cle' => 'validation', 'libelle' => 'Validation par DAG', 'acteur' => 'DAG'],
            ];

        $courante = match ($etape) {
            'EN_ETUDE', 'REFUSE' => 'etude',
            'A_SIGNER', 'EN_REDACTION', 'ANNULE' => 'signature',
            'A_VALIDER' => 'validation',
            default => null,
        };

        $termine = in_array($etape, ['ACTIF', 'TERMINE'], true);
        $indexCourant = $courante !== null ? array_search($courante, array_column($definition, 'cle'), true) : false;

        $etapes = array_map(function (array $ligne, int $index) use ($indexCourant, $termine, $vueClient): array {
            return [
                ...$ligne,
                'libelleActeur' => self::libelleActeur($ligne['acteur'], $vueClient),
                'etat' => match (true) {
                    $termine => 'faite',
                    $indexCourant === false => 'a_venir',
                    $index < $indexCourant => 'faite',
                    $index === $indexCourant => 'en_cours',
                    default => 'a_venir',
                },
            ];
        }, $definition, array_keys($definition));

        return [
            'etapes' => $etapes,
            'prochaine' => self::prochaine($etape, $vueClient),
            'termine' => $termine,
            'interrompu' => match ($etape) {
                'REFUSE' => $vueClient
                    ? "Votre demande n'a pas été retenue. Motif : {$partenariat->motif_refus}"
                    : "Demande refusée. Motif : {$partenariat->motif_refus}",
                'ANNULE' => "Le contrat de partenariat n'a pas été signé : le partenariat est annulé.",
                default => null,
            },
        ];
    }

    private static function libelleActeur(string $acteur, bool $vueClient): string
    {
        return match (true) {
            $acteur === 'DAG' => 'Équipe DAG',
            $vueClient => 'Vous',
            default => 'Structure',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function prochaine(string $etape, bool $vueClient): ?array
    {
        $prochaine = match ($etape) {
            'EN_ETUDE' => [
                'cle' => 'etude',
                'acteur' => 'DAG',
                'titre' => 'Étude de la demande',
                'texte' => $vueClient
                    ? "L'équipe DAG étudie votre demande et vous répond rapidement. Si elle l'accepte, le contrat de partenariat vous est envoyé ici même."
                    : 'Acceptez la demande en choisissant la durée du partenariat : le contrat est rédigé et envoyé à la structure. Sinon, refusez-la avec un motif.',
                'action' => 'etudier',
            ],
            'A_SIGNER' => [
                'cle' => 'signature',
                'acteur' => 'CLIENT',
                'titre' => 'Signature du contrat de partenariat',
                'texte' => $vueClient
                    ? "Lisez le contrat, importez l'image de votre signature, puis confirmez. Cela ne prend que quelques minutes."
                    : 'Le contrat attend la signature de la structure. Elle est prévenue et le retrouve dans son espace.',
                'action' => 'signer_contrat',
            ],
            'EN_REDACTION' => [
                'cle' => 'signature',
                'acteur' => 'DAG',
                'titre' => 'Révision du contrat',
                'texte' => $vueClient
                    ? "L'équipe DAG met à jour votre contrat de partenariat. Il vous sera renvoyé pour signature."
                    : 'Le contrat est repassé en brouillon : corrigez-le puis renvoyez-le à la structure depuis la page du contrat.',
                'action' => 'reviser_contrat',
            ],
            'A_VALIDER' => [
                'cle' => 'validation',
                'acteur' => 'DAG',
                'titre' => 'Validation du contrat signé',
                'texte' => $vueClient
                    ? "Merci pour votre signature. L'équipe DAG valide le contrat, puis votre structure devient Partenaire officiel de Digital Agency Group."
                    : 'La structure a signé. Validez le contrat pour en faire un Partenaire officiel de DAG.',
                'action' => 'valider_contrat',
            ],
            default => null,
        };

        if ($prochaine === null) {
            return null;
        }

        return [
            ...$prochaine,
            'libelleActeur' => self::libelleActeur($prochaine['acteur'], $vueClient),
            'aVous' => $vueClient ? $prochaine['acteur'] === 'CLIENT' : $prochaine['acteur'] === 'DAG',
            'information' => false,
        ];
    }
}
