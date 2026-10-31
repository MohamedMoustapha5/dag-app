<?php

namespace Database\Seeders;

use App\Models\Abonnement;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Candidature;
use App\Models\Clause;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Formule;
use App\Models\Inscription;
use App\Models\MembreEquipe;
use App\Models\ModeleContrat;
use App\Models\NotificationLog;
use App\Models\OffreEmploi;
use App\Models\Paiement;
use App\Models\Presence;
use App\Models\ProfilPropose;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Ties the reference data together into a believable end-to-end demo:
 * a client with an in-progress request and an active subscription, a
 * partner company with a candidate pipeline, and the supporting legal
 * / billing / notification trail. Mirrors the scenarios shown in the
 * HTML mockups (espace-client, espace-entreprise, espace-admin).
 */
class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->clausesEtModeles();
        $this->parcoursClientAicha();
        $this->parcoursEntrepriseKiro();
        $this->parcoursFormationEtPlacement();
        $this->journalEtNotifications();
    }

    private function clausesEtModeles(): void
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
        ModeleContrat::create(['nom' => 'Contrat partenaire (Nova-Tech)', 'type' => 'CONTRAT_PARTENAIRE', 'contenu' => 'Contrat-cadre de partenariat entre DAG et {{entreprise}}.']);
    }

    private function parcoursClientAicha(): void
    {
        $aicha = Client::whereHas('user', fn ($q) => $q->where('email', 'aicha.n@exemple.com'))->first();
        $campagnes = Service::where('nom', 'Campagnes publicitaires')->first();
        $croissance = Formule::where('nom', 'Croissance')->first();
        $modelePrestation = ModeleContrat::where('type', 'PRESTATION_SERVICE')->first();
        $modeleAbonnement = ModeleContrat::where('type', 'ABONNEMENT')->first();

        // Demande en cours, contrat envoyé, facture payée (campagne Meta Ads).
        $demande = Demande::create([
            'client_id' => $aicha->id,
            'service_id' => $campagnes->id,
            'description' => 'Campagne publicitaire Meta Ads — portée et conversion, 30 jours.',
            'montant_negocie' => 85000,
            'statut' => 'EN_COURS',
        ]);

        $contrat = Contrat::create([
            'type' => 'PRESTATION_SERVICE',
            'modele_contrat_id' => $modelePrestation->id,
            'client_id' => $aicha->id,
            'demande_id' => $demande->id,
            'montant' => 85000,
            'statut' => 'ENVOYE',
            'derniere_relance_at' => now()->subDays(1),
        ]);

        $facture = Facture::create([
            'type' => 'SERVICE',
            'client_id' => $aicha->id,
            'demande_id' => $demande->id,
            'contrat_id' => $contrat->id,
            'montant' => 85000,
            'montant_paye' => 85000,
            'statut' => 'PAYEE',
            'moyen_paiement' => 'MTN_MOMO',
        ]);

        Paiement::create([
            'facture_id' => $facture->id,
            'montant' => 85000,
            'reference_transaction' => 'TX-28471-CM',
            'moyen' => 'MTN_MOMO',
            'statut' => 'CONFIRME',
            'date_paiement' => now()->subDays(3),
        ]);

        // Abonnement actif, signé, avec échéances déjà facturées.
        $abonnement = Abonnement::create([
            'client_id' => $aicha->id,
            'formule_id' => $croissance->id,
            'montant_negocie' => 120000,
            'date_souscription' => '2026-09-01',
            'cycle_debut' => '2026-10-15',
            'echeance' => '2026-11-15',
            'statut' => 'ACTIF',
        ]);

        Contrat::create([
            'type' => 'ABONNEMENT',
            'modele_contrat_id' => $modeleAbonnement->id,
            'client_id' => $aicha->id,
            'abonnement_id' => $abonnement->id,
            'montant' => 120000,
            'statut' => 'ACTIF',
            'photo_signature' => 'signatures/demo-aicha.png',
            'signataire_nom' => 'Aïcha N.',
            'ip_signature' => '102.165.41.08',
            'date_signature' => now()->subMonth(),
        ]);

        foreach (['2026-08-01', '2026-09-01', '2026-10-01'] as $mois) {
            $factureAbo = Facture::create([
                'type' => 'ABONNEMENT',
                'client_id' => $aicha->id,
                'abonnement_id' => $abonnement->id,
                'montant' => 120000,
                'montant_paye' => 120000,
                'statut' => 'PAYEE',
                'moyen_paiement' => 'MTN_MOMO',
                'echeance' => $mois,
            ]);

            Paiement::create([
                'facture_id' => $factureAbo->id,
                'montant' => 120000,
                'moyen' => 'MTN_MOMO',
                'statut' => 'CONFIRME',
                'date_paiement' => $mois,
            ]);
        }

        // Boost de page Instagram — devis envoyé, en attente de paiement.
        Demande::create([
            'client_id' => $aicha->id,
            'service_id' => Service::where('nom', 'Boost de comptes et de pages')->first()->id,
            'description' => 'Boost de la page Instagram pendant 15 jours.',
            'montant_negocie' => 45000,
            'statut' => 'DEVIS_ENVOYE',
        ]);

        // Demande partenaire Nova-Tech, déjà livrée.
        Demande::create([
            'client_id' => $aicha->id,
            'service_id' => Service::where('nom', 'Création de sites internet')->first()->id,
            'description' => 'Site e-commerce — boutique en ligne.',
            'statut' => 'TERMINEE',
            'est_partenaire_nova_tech' => true,
            'avancement_partenaire' => 'LIVRE',
        ]);

        $fatouBoutique = Client::whereHas('user', fn ($q) => $q->where('email', 'contact@fatouboutique.cm'))->first();
        Demande::create([
            'client_id' => $fatouBoutique->id,
            'description' => 'Audit de page gratuit — page Facebook.',
            'statut' => 'BROUILLON',
        ]);
    }

    private function parcoursEntrepriseKiro(): void
    {
        $kiro = EntreprisePartenaire::where('nom', 'Kiro Digital Solutions')->first();
        $atlas = EntreprisePartenaire::where('nom', 'Atlas Corp')->first();
        $modelePartenaire = ModeleContrat::where('type', 'CONTRAT_PARTENAIRE')->first();
        $modeleAbonnement = ModeleContrat::where('type', 'ABONNEMENT')->first();
        $croissance = Formule::where('nom', 'Croissance')->first();

        // Validated as an official recruitment partner: its self-service
        // account unlocks the offres/candidatures espace.
        $kiro->update([
            'client_id' => Client::whereHas('user', fn ($q) => $q->where('email', 'contact@kirodigital.cm'))->first()->id,
        ]);

        Contrat::create([
            'type' => 'CONTRAT_PARTENAIRE',
            'modele_contrat_id' => $modelePartenaire->id,
            'entreprise_id' => $kiro->id,
            'statut' => 'ACTIF',
            'photo_signature' => 'signatures/demo-kiro.png',
            'signataire_nom' => 'Kiro N.',
            'date_signature' => '2026-01-01 09:00:00',
        ]);

        $abonnementKiro = Abonnement::create([
            'entreprise_id' => $kiro->id,
            'formule_id' => $croissance->id,
            'montant_negocie' => 180000,
            'date_souscription' => '2026-09-01',
            'cycle_debut' => '2026-09-01',
            'echeance' => '2026-11-01',
            'statut' => 'ACTIF',
        ]);

        Contrat::create([
            'type' => 'ABONNEMENT',
            'modele_contrat_id' => $modeleAbonnement->id,
            'entreprise_id' => $kiro->id,
            'abonnement_id' => $abonnementKiro->id,
            'montant' => 180000,
            'statut' => 'ACTIF',
            'photo_signature' => 'signatures/demo-kiro.png',
            'signataire_nom' => 'Kiro N.',
            'date_signature' => '2026-09-01 10:00:00',
        ]);

        $factureKiro = Facture::create([
            'type' => 'ABONNEMENT',
            'entreprise_id' => $kiro->id,
            'abonnement_id' => $abonnementKiro->id,
            'montant' => 180000,
            'montant_paye' => 180000,
            'statut' => 'PAYEE',
            'moyen_paiement' => 'OM',
            'echeance' => '2026-10-01',
        ]);

        Paiement::create([
            'facture_id' => $factureKiro->id,
            'montant' => 180000,
            'reference_transaction' => 'PMT-2026-0914',
            'moyen' => 'OM',
            'statut' => 'CONFIRME',
            'date_paiement' => '2026-10-01',
        ]);

        // Demande de service DAG déposée depuis l'espace entreprise, pas encore traitée.
        Demande::create([
            'entreprise_id' => $kiro->id,
            'service_id' => Service::where('nom', 'Campagnes publicitaires')->first()->id,
            'description' => 'Campagne de recrutement sur les réseaux sociaux.',
            'statut' => 'BROUILLON',
        ]);

        // Demande transmise à Nova-Tech pour Atlas Corp, en attente de devis.
        Demande::create([
            'entreprise_id' => $atlas->id,
            'service_id' => Service::where('nom', 'Création de sites internet')->first()->id,
            'description' => 'Refonte du site vitrine Atlas Corp.',
            'statut' => 'EN_ETUDE',
            'est_partenaire_nova_tech' => true,
            'avancement_partenaire' => 'A_TRANSMETTRE',
        ]);
    }

    private function parcoursFormationEtPlacement(): void
    {
        $session = FormationSession::where('ville', 'Douala')->where('statut', 'OUVERTE')->firstOrFail();
        $seances = $session->seances()->orderBy('date')->get();

        $emails = [
            'stephanie.a@mail.com', 'brice.k@mail.com', 'noura.e@mail.com', 'patrick.m@mail.com',
            'carine.f@mail.com', 'junior.a@mail.com', 'larissa.t@mail.com', 'samuel.e@mail.com',
            'grace.n@mail.com', 'yannick.b@mail.com',
        ];
        $enAttente = ['patrick.m@mail.com', 'samuel.e@mail.com'];

        $inscriptionsParEmail = [];

        foreach ($emails as $email) {
            $apprenant = Apprenant::whereHas('user', fn ($q) => $q->where('email', $email))->first();

            $inscription = Inscription::create([
                'apprenant_id' => $apprenant->id,
                'formation_id' => $session->formation_id,
                'formation_session_id' => $session->id,
                'montant_negocie' => 60000,
                'statut' => in_array($email, $enAttente, true) ? 'EN_ATTENTE_PAIEMENT' : 'VALIDEE',
            ]);

            $inscriptionsParEmail[$email] = $inscription;

            if (! in_array($email, $enAttente, true)) {
                Facture::create([
                    'type' => 'FORMATION',
                    'apprenant_id' => $apprenant->id,
                    'inscription_id' => $inscription->id,
                    'montant' => 60000,
                    'montant_paye' => 60000,
                    'statut' => 'PAYEE',
                    'moyen_paiement' => 'OM',
                ]);

                foreach ($seances->take(9) as $seance) {
                    Presence::create([
                        'seance_id' => $seance->id,
                        'inscription_id' => $inscription->id,
                        'present' => true,
                    ]);
                }
            }
        }

        // Candidatures, matching the offre-detail.html / dossier-apprenant.html mockups.
        $offreKiro = OffreEmploi::where('titre', 'Community Manager junior')->first();
        $offreAtlas = OffreEmploi::where('titre', 'Chargé(e) de création de contenu')->first();

        $stephanie = Apprenant::whereHas('user', fn ($q) => $q->where('email', 'stephanie.a@mail.com'))->first();
        $brice = Apprenant::whereHas('user', fn ($q) => $q->where('email', 'brice.k@mail.com'))->first();
        $noura = Apprenant::whereHas('user', fn ($q) => $q->where('email', 'noura.e@mail.com'))->first();

        Candidature::create(['apprenant_id' => $stephanie->id, 'offre_id' => $offreKiro->id, 'statut' => 'EN_ETUDE']);
        $candidatureBrice = Candidature::create(['apprenant_id' => $brice->id, 'offre_id' => $offreKiro->id, 'statut' => 'RETENUE']);
        Candidature::create(['apprenant_id' => $noura->id, 'offre_id' => $offreAtlas->id, 'statut' => 'REFUSEE']);

        $modeleConvention = ModeleContrat::where('type', 'CONVENTION_STAGE')->first();
        $kiro = EntreprisePartenaire::where('nom', 'Kiro Digital Solutions')->first();

        Contrat::create([
            'type' => 'CONVENTION_STAGE',
            'modele_contrat_id' => $modeleConvention->id,
            'apprenant_id' => $brice->id,
            'entreprise_id' => $kiro->id,
            'candidature_id' => $candidatureBrice->id,
            'statut' => 'ACTIF',
            'photo_signature' => 'signatures/demo-brice.png',
            'signataire_nom' => 'Brice K.',
            'date_signature' => '2026-09-15 09:00:00',
        ]);

        // A second retenue on Kiro's offer, awaiting her own signature —
        // gives the entreprise espace a "convention à signer" to show.
        $carine = Apprenant::whereHas('user', fn ($q) => $q->where('email', 'carine.f@mail.com'))->first();
        $candidatureCarine = Candidature::create(['apprenant_id' => $carine->id, 'offre_id' => $offreKiro->id, 'statut' => 'RETENUE']);

        Contrat::create([
            'type' => 'CONVENTION_STAGE',
            'modele_contrat_id' => $modeleConvention->id,
            'apprenant_id' => $carine->id,
            'entreprise_id' => $kiro->id,
            'candidature_id' => $candidatureCarine->id,
            'statut' => 'ENVOYE',
            'derniere_relance_at' => now()->subDays(2),
        ]);

        // A profile DAG suggests directly to Kiro, outside any offre.
        $junior = Apprenant::whereHas('user', fn ($q) => $q->where('email', 'junior.a@mail.com'))->first();

        ProfilPropose::create([
            'entreprise_id' => $kiro->id,
            'apprenant_id' => $junior->id,
            'membre_equipe_id' => MembreEquipe::first()?->id,
            'descriptif' => 'Stratégie digitale & campagnes publicitaires',
            'statut' => 'PROPOSE',
        ]);

        // A completed past session, with an attestation delivered.
        $ancienneFormation = Formation::create([
            'nom' => 'Initiation aux réseaux sociaux',
            'description' => 'Prise en main des principaux réseaux sociaux pour une activité professionnelle.',
            'categorie' => 'Community management',
            'duree' => '3 semaines',
            'statut' => 'TERMINEE',
        ]);

        FormationSession::create([
            'formation_id' => $ancienneFormation->id,
            'ville' => 'Douala',
            'mode' => 'PRESENTIEL',
            'date_debut' => '2026-08-03',
            'date_fin' => '2026-08-21',
            'places' => 15,
            'statut' => 'TERMINEE',
        ]);

        Attestation::create([
            'apprenant_id' => $stephanie->id,
            'formation_id' => $ancienneFormation->id,
            'date_emission' => '2026-08-21',
            'fichier_pdf' => 'attestations/demo-stephanie.pdf',
        ]);
    }

    private function journalEtNotifications(): void
    {
        $promotrice = MembreEquipe::whereHas('user', fn ($q) => $q->where('email', 'marie.d@dag.cm'))->first();
        $juriste = MembreEquipe::whereHas('user', fn ($q) => $q->where('email', 'fatou.m@dag.cm'))->first();
        $communication = MembreEquipe::whereHas('user', fn ($q) => $q->where('email', 'jules.k@dag.cm'))->first();

        $contratAicha = Contrat::where('client_id', Client::whereHas('user', fn ($q) => $q->where('email', 'aicha.n@exemple.com'))->first()?->id)
            ->where('type', 'PRESTATION_SERVICE')->first();

        ActivityLog::record($juriste, 'a validé le contrat', $contratAicha, 'Contrat de prestation de service contrôlé avant envoi.');
        ActivityLog::record($communication, 'a transmis une demande à Nova-Tech', null, 'Création de site internet — Atlas Corp.');
        ActivityLog::record($promotrice, 'a créé un compte apprenant', null, 'Compte de Rosine T. — rôle Création de contenu.');

        $aichaUser = Client::whereHas('user', fn ($q) => $q->where('email', 'aicha.n@exemple.com'))->first()->user;

        NotificationLog::create([
            'user_id' => $aichaUser->id,
            'evenement' => 'devis_envoye',
            'canal' => 'EMAIL',
            'titre' => 'Devis envoyé — Campagne Meta Ads',
            'message' => 'Votre devis de 85 000 FCFA pour la campagne Meta Ads est disponible dans votre espace client.',
            'statut' => 'ENVOYE',
            'envoye_at' => now()->subDays(3),
        ]);

        NotificationLog::create([
            'user_id' => $aichaUser->id,
            'evenement' => 'paiement_confirme',
            'canal' => 'EMAIL',
            'titre' => 'Paiement confirmé — Audit de page',
            'message' => 'Votre paiement a été confirmé. Un reçu numérique est disponible dans votre espace.',
            'statut' => 'ENVOYE',
            'envoye_at' => now()->subDay(),
        ]);
    }
}
