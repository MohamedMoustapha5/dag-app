<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantFormationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user('web')->apprenant;

        $formations = Formation::where('statut', 'PUBLIEE')
            ->with(['sessions' => fn ($query) => $query->whereIn('statut', ['OUVERTE', 'COMPLETE'])->orderBy('date_debut')])
            ->get();

        $sessions = [];

        foreach ($formations as $formation) {
            foreach ($formation->sessions as $session) {
                $sessions[] = $this->mapSession($session, $formation);
            }
        }

        return Inertia::render('espaces/apprenant/formations-index', [
            'apprenant' => $apprenant->espaceHeaderProps(),
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
            'prix' => $session->prix,
            'peutSInscrire' => $session->prix !== null && $session->statut === 'OUVERTE' && $session->placesRestantes() > 0,
            'inscriptionUrl' => route('espace-apprenant.inscriptions.create', $session),
            'statut' => $session->statut,
            'image' => $formation->image,
        ];
    }
}
