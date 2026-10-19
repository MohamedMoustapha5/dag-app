<?php

namespace Database\Seeders;

use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\Seance;
use Illuminate\Database\Seeder;

class FormationSeeder extends Seeder
{
    public function run(): void
    {
        $communityManagement = Formation::create([
            'nom' => 'Community Management — Fondamentaux',
            'description' => 'Apprenez à planifier, publier et modérer sur les réseaux sociaux. Formation pratique, orientée mise en situation, avec accompagnement vers un stage ou un emploi à l\'issue de la session.',
            'programme' => "Semaine 1 — Fondamentaux des réseaux sociaux et stratégie éditoriale\nSemaine 2 — Création de contenu, planification, outils\nSemaine 3 — Modération, mesure de performance, projet final",
            'categorie' => 'Community management',
            'duree' => '3 semaines',
            'prerequis' => 'Aucun — niveau débutant',
            'statut' => 'PUBLIEE',
        ]);

        $session = FormationSession::create([
            'formation_id' => $communityManagement->id,
            'ville' => 'Douala',
            'mode' => 'PRESENTIEL',
            'lieu' => 'Douala — Bonapriso',
            'formateur' => 'Sylvie Mballa',
            'date_debut' => '2026-11-10',
            'date_fin' => '2026-11-28',
            'places' => 15,
            'statut' => 'OUVERTE',
        ]);

        foreach (range(1, 14) as $numero) {
            Seance::create([
                'formation_session_id' => $session->id,
                'titre' => 'Séance '.$numero,
                'date' => now()->parse('2026-11-10')->addWeekdays($numero - 1),
            ]);
        }

        $creationContenu = Formation::create([
            'nom' => 'Création de contenu digital',
            'description' => 'Visuels, vidéos courtes et branding pour les réseaux sociaux et le web.',
            'programme' => "Fondamentaux du design et de la ligne éditoriale\nOutils de création (Canva, montage vidéo)\nPlanification et publication multi-plateformes",
            'categorie' => 'Création de contenu',
            'duree' => '3 semaines',
            'prerequis' => 'Aucun',
            'statut' => 'PUBLIEE',
        ]);

        FormationSession::create([
            'formation_id' => $creationContenu->id,
            'ville' => 'Douala',
            'mode' => 'LES_DEUX',
            'lieu' => 'Douala — Akwa',
            'formateur' => 'R. Tchoupo',
            'date_debut' => '2026-12-02',
            'date_fin' => '2026-12-20',
            'places' => 15,
            'statut' => 'COMPLETE',
        ]);

        $strategieDigitale = Formation::create([
            'nom' => 'Stratégie digitale & campagnes publicitaires',
            'description' => 'Conception de stratégies de publicité ciblée et pilotage de campagnes Meta Ads / TikTok Ads.',
            'programme' => "Audit et objectifs de campagne\nCiblage, budget et formats publicitaires\nSuivi de performance et optimisation",
            'categorie' => 'Stratégie & publicité',
            'duree' => '3 semaines',
            'prerequis' => 'Notions de base en marketing digital',
            'statut' => 'PUBLIEE',
        ]);

        FormationSession::create([
            'formation_id' => $strategieDigitale->id,
            'ville' => 'Yaoundé',
            'mode' => 'EN_LIGNE',
            'lieu' => 'Yaoundé — Bastos',
            'formateur' => 'J. Ekotto',
            'date_debut' => '2027-01-14',
            'date_fin' => '2027-02-01',
            'places' => 15,
            'statut' => 'OUVERTE',
        ]);
    }
}
