<?php

namespace Database\Seeders;

use App\Models\Clause;
use App\Models\ModeleContrat;
use Illuminate\Database\Seeder;

/**
 * The contract clauses and templates DAG drafts its contracts from:
 * reference data, needed in production as much as in the demo.
 */
class ClausesEtModelesSeeder extends Seeder
{
    public function run(): void
    {
        $clauses = [
            ['titre' => 'Objet & prestations', 'contenu' => 'Le présent contrat a pour objet la réalisation, par DAG, des prestations décrites à l\'article 1, selon les modalités, délais et livrables convenus avec le client.'],
            ['titre' => 'Montant & modalités de paiement', 'contenu' => 'Le montant de la prestation est fixé selon le devis accepté, payable en ligne via Orange Money, MTN Mobile Money ou carte bancaire, intégralement avant le démarrage de la prestation.'],
            ['titre' => 'Durée & résiliation', 'contenu' => 'Le contrat prend effet à sa signature et court jusqu\'à la livraison complète des prestations. Toute résiliation anticipée doit être notifiée par écrit avec un préavis de 15 jours.'],
            ['titre' => 'Confidentialité', 'contenu' => 'Les parties s\'engagent à garder confidentielles toutes les informations échangées dans le cadre de ce contrat, pendant toute sa durée et deux ans après son terme.'],
            ['titre' => 'Propriété intellectuelle', 'contenu' => 'Les livrables créés par DAG deviennent la propriété exclusive du client après paiement intégral, sauf éléments réutilisables appartenant au fonds propre de DAG.'],
        ];

        foreach ($clauses as $index => $clause) {
            Clause::create($clause + ['ordre' => $index]);
        }

        ModeleContrat::create(['nom' => 'Prestation de service', 'type' => 'PRESTATION_SERVICE', 'contenu' => 'Contrat de prestation de service entre DAG et {{client}} pour {{service}}, au montant de {{montant}} FCFA.']);
        ModeleContrat::create(['nom' => 'Abonnement', 'type' => 'ABONNEMENT', 'contenu' => "Contrat d'abonnement à la formule {{formule}} entre DAG et {{client}}, au montant mensuel de {{montant}} FCFA."]);
        ModeleContrat::create(['nom' => 'Convention de stage', 'type' => 'CONVENTION_STAGE', 'contenu' => 'Convention de stage entre DAG, {{entreprise}} et {{apprenant}} pour le poste de {{poste}}.']);
        ModeleContrat::create(['nom' => 'Contrat partenaire', 'type' => 'CONTRAT_PARTENAIRE', 'contenu' => 'Contrat-cadre de partenariat entre DAG et {{entreprise}}.']);
    }
}
