<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationSession;
use Inertia\Inertia;
use Inertia\Response;

class FormationController extends Controller
{
    public function index(): Response
    {
        $formations = Formation::where('statut', 'PUBLIEE')
            ->with(['sessions' => fn ($query) => $query->whereIn('statut', ['OUVERTE', 'COMPLETE'])->orderBy('date_debut')])
            ->get();

        $sessions = [];

        foreach ($formations as $formation) {
            foreach ($formation->sessions as $session) {
                $sessions[] = $this->mapSession($session, $formation);
            }
        }

        $placesMoyennes = count($sessions) > 0
            ? (int) round(array_sum(array_column($sessions, 'places')) / count($sessions))
            : 0;

        return Inertia::render('public/formations', [
            'sessions' => $sessions,
            'stats' => [
                'placesMoyennes' => $placesMoyennes,
                // Le taux de placement réel nécessiterait de croiser
                // Candidature/Contrat sur l'ensemble des apprenants formés
                // (pas encore suivi) — valeur de communication en attendant,
                // comme les autres chiffres fixes de la vitrine publique.
                'tauxPlacement' => 68,
            ],
        ]);
    }

    public function show(Formation $formation): Response
    {
        $sessionModels = $formation->sessions()
            ->whereIn('statut', ['OUVERTE', 'COMPLETE'])
            ->orderBy('date_debut')
            ->get();

        $sessions = [];

        foreach ($sessionModels as $session) {
            $sessions[] = [
                'id' => $session->id,
                'ville' => $session->ville,
                'mode' => $session->mode,
                'lieu' => $session->lieu,
                'formateur' => $session->formateur,
                'dateDebut' => $session->date_debut?->toDateString(),
                'dateFin' => $session->date_fin?->toDateString(),
                'placesRestantes' => $session->placesRestantes(),
                'statut' => $session->statut,
            ];
        }

        return Inertia::render('public/formation-detail', [
            'formation' => [
                'id' => $formation->id,
                'nom' => $formation->nom,
                'description' => $formation->description,
                'programme' => $formation->programme,
                'duree' => $formation->duree,
                'prerequis' => $formation->prerequis,
                'categorie' => $formation->categorie,
            ],
            'sessions' => $sessions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSession(FormationSession $session, Formation $formation): array
    {
        return [
            'formationId' => $formation->id,
            'nom' => $formation->nom,
            'categorie' => $formation->categorie,
            'sessionId' => $session->id,
            'ville' => $session->ville,
            'mode' => $session->mode,
            'lieu' => $session->lieu,
            'formateur' => $session->formateur,
            'dateDebut' => $session->date_debut?->toDateString(),
            'dateFin' => $session->date_fin?->toDateString(),
            'places' => $session->places,
            'placesRestantes' => $session->placesRestantes(),
            'statut' => $session->statut,
            'image' => $formation->image,
        ];
    }
}
