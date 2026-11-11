<?php

namespace Database\Seeders;

use App\Models\Clause;
use App\Models\ModeleContrat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Installs the DAG contract written by the juriste: the articles become the
 * clause library and the prestation / abonnement modèles become active. The
 * identity of the client (ID card, phone, city, place of service) is not in
 * this text: the client gives it when signing and it is printed in the
 * "Parties" block of the contract.
 *
 * Safe to run again: it replaces the clause library and re-activates these
 * modèles. Contracts already drafted keep the text they were drafted with.
 */
class ContratDagSeeder extends Seeder
{
    public const NOM_MODELE_PRESTATION = 'Contrat DAG — Prestation de service';

    public const NOM_MODELE_ABONNEMENT = 'Contrat DAG — Abonnement';

    public function run(): void
    {
        DB::transaction(function (): void {
            Clause::query()->delete();

            foreach (self::articles() as $ordre => $article) {
                Clause::create([...$article, 'ordre' => $ordre]);
            }

            $this->activerModele(self::NOM_MODELE_PRESTATION, 'PRESTATION_SERVICE', <<<'TXT'
                Le présent contrat de prestation de service est conclu entre DAG — Digital Agency Group, ci-après « DAG », et {{client}}, ci-après « l'intéressé », dont l'identité figure en tête du contrat.

                Il se rapporte à la demande {{reference}} et a été établi le {{date}}.
                TXT);

            $this->activerModele(self::NOM_MODELE_ABONNEMENT, 'ABONNEMENT', <<<'TXT'
                Le présent contrat d'abonnement à la formule {{formule}} est conclu entre DAG — Digital Agency Group, ci-après « DAG », et {{client}}, ci-après « l'intéressé », dont l'identité figure en tête du contrat.

                Il se rapporte à la demande {{reference}} et a été établi le {{date}}.
                TXT);
        });
    }

    /**
     * The articles of the DAG contract, in order.
     *
     * @return list<array{titre: string, contenu: string}>
     */
    public static function articles(): array
    {
        return [
            [
                'titre' => 'Objet de la demande',
                'contenu' => "Le présent contrat porte sur : {{service}}.\n\nDétail de la demande de l'intéressé : {{description}}",
            ],
            [
                'titre' => 'Coût du service',
                'contenu' => "Le coût du service est fixé à {{montant}} FCFA, selon l'évaluation de la Gérante de DAG ou, pour un service à prix forfaitaire, selon le prix unitaire multiplié par la quantité souhaitée par l'intéressé.",
            ],
            [
                'titre' => 'Délai de prestation',
                'contenu' => 'La prestation est réalisée dans un délai de trois (03) jours minimum et de sept (07) jours maximum à compter de la signature du présent contrat.',
            ],
            [
                'titre' => 'Rectification du contrat',
                'contenu' => "L'intéressé dispose de deux (02) possibilités de révision du présent contrat. Au-delà de deux (02) demandes de rectification, un nouveau contrat doit être généré, puis lu et approuvé par l'Administration de DAG.",
            ],
            [
                'titre' => 'Annulation',
                'contenu' => implode("\n\n", [
                    "En cas d'annulation de la commande par l'intéressé, le remboursement de la somme versée s'effectue comme suit :",
                    '- annulation le jour même de la commande : l\'intéressé est intégralement remboursé ;',
                    '- annulation le lendemain de la commande : l\'intéressé est remboursé des trois quarts (3/4) de la somme versée ;',
                    '- annulation le jour de la livraison : l\'intéressé est remboursé de la moitié (1/2) de la somme versée ;',
                    '- une fois la livraison et les tâches effectuées, l\'intéressé ne peut plus demander la restitution de la somme versée.',
                ]),
            ],
            [
                'titre' => 'Confidentialité',
                'contenu' => "Le présent contrat, signé et approuvé par les parties, reste strictement confidentiel entre l'intéressé et la société DAG. Il ne peut faire l'objet d'aucune divulgation, sauf en cas de force majeure.",
            ],
            [
                'titre' => 'Réservation',
                'contenu' => "Après présentation du contrat généré par DAG, l'intéressé peut réserver la prestation en versant un acompte de mille (1 000) francs CFA, valable deux (02) jours. Cet acompte n'est pas remboursable si l'intéressé ne signe pas définitivement le contrat.",
            ],
        ];
    }

    /**
     * Creates (or updates) the modèle and makes it the active one of its
     * type; the other modèles of that type are kept, as drafts.
     */
    private function activerModele(string $nom, string $type, string $contenu): void
    {
        ModeleContrat::query()
            ->where('type', $type)
            ->where('nom', '!=', $nom)
            ->update(['statut' => 'BROUILLON']);

        ModeleContrat::updateOrCreate(
            ['nom' => $nom],
            ['type' => $type, 'contenu' => $contenu, 'statut' => 'ACTIF'],
        );
    }
}
