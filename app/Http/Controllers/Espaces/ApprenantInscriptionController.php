<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Support\Notifications\Notifier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantInscriptionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user('web')->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->with(['formation', 'formationSession.seances'])
            ->latest()
            ->get();

        return Inertia::render('espaces/apprenant/inscriptions-index', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'inscriptions' => $inscriptions->map(fn (Inscription $inscription) => $this->mapInscription($inscription))->all(),
        ]);
    }

    /**
     * Confirmation screen of an enrolment, reached from the public "S'inscrire"
     * button (after signing in as an apprenant when needed).
     */
    public function create(Request $request, FormationSession $formationSession): Response|RedirectResponse
    {
        $apprenant = $request->user('web')->apprenant;
        $formationSession->load('formation');

        if ($this->dejaInscrit($apprenant, $formationSession)) {
            return $this->dejaInscritReponse();
        }

        return Inertia::render('espaces/apprenant/inscription-create', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'session' => [
                'id' => $formationSession->id,
                'formation' => $formationSession->formation->nom,
                'categorie' => $formationSession->formation->libelleCategorie(),
                'ville' => $formationSession->ville,
                'lieu' => $formationSession->lieu,
                'mode' => $formationSession->mode,
                'formateur' => $formationSession->formateur,
                'dateDebut' => $this->formatDate($formationSession->date_debut),
                'dateFin' => $this->formatDate($formationSession->date_fin),
                'placesRestantes' => $formationSession->placesRestantes(),
                'ouverte' => $formationSession->statut === 'OUVERTE' && $formationSession->formation->statut === 'PUBLIEE',
            ],
        ]);
    }

    public function store(Request $request, FormationSession $formationSession): RedirectResponse
    {
        $apprenant = $request->user('web')->apprenant;
        $formationSession->load('formation');

        if ($this->dejaInscrit($apprenant, $formationSession)) {
            return $this->dejaInscritReponse();
        }

        abort_unless(
            $formationSession->statut === 'OUVERTE' && $formationSession->formation->statut === 'PUBLIEE',
            409,
            'Les inscriptions à cette session ne sont pas ouvertes.'
        );

        abort_if($formationSession->placesRestantes() <= 0, 409, 'Cette session est complète.');

        $inscription = Inscription::create([
            'apprenant_id' => $apprenant->id,
            'formation_id' => $formationSession->formation_id,
            'formation_session_id' => $formationSession->id,
            'statut' => 'EN_ATTENTE_PAIEMENT',
        ]);

        Notifier::alerterEquipe(
            Donnee::Formations,
            Action::Valider,
            'inscription.nouvelle',
            "Nouvelle inscription — {$formationSession->formation->nom}",
            "{$apprenant->user->name} demande à s'inscrire à la session de {$formationSession->ville}. Elle attend la validation du paiement.",
            route('espace-admin.sessions.show', $formationSession),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Inscription enregistrée : l\'équipe DAG vous contacte pour le paiement et valide votre place.',
        ]);

        return to_route('espace-apprenant.inscriptions');
    }

    private function dejaInscrit(Apprenant $apprenant, FormationSession $session): bool
    {
        return $apprenant->inscriptions()
            ->where('formation_session_id', $session->id)
            ->where('statut', '!=', 'ABANDONNEE')
            ->exists();
    }

    private function dejaInscritReponse(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vous êtes déjà inscrit à cette session.']);

        return to_route('espace-apprenant.inscriptions');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapInscription(Inscription $inscription): array
    {
        $seances = $inscription->formationSession?->seances;
        $dateDebut = $inscription->formationSession?->date_debut;

        return [
            'id' => $inscription->id,
            'formation' => $inscription->formation->nom,
            'ville' => $inscription->formationSession?->ville,
            'mode' => $inscription->formationSession?->mode,
            'dateDebut' => $this->formatDate($dateDebut),
            'dateFin' => $this->formatDate($inscription->formationSession?->date_fin),
            'statut' => $inscription->statut,
            'progression' => $this->progression($inscription, $seances),
            'semainesAvantDebut' => $this->semainesAvantDebut($dateDebut),
        ];
    }

    /**
     * @param  Collection<int, Seance>|null  $seances
     */
    private function progression(Inscription $inscription, ?Collection $seances): int
    {
        if ($seances === null || $seances->isEmpty()) {
            return 0;
        }

        $presentes = Presence::whereIn('seance_id', $seances->pluck('id'))
            ->where('inscription_id', $inscription->id)
            ->where('present', true)
            ->count();

        return (int) round($presentes / $seances->count() * 100);
    }

    private function semainesAvantDebut(?string $dateDebut): ?int
    {
        if ($dateDebut === null) {
            return null;
        }

        $debut = Carbon::parse($dateDebut);

        if ($debut->isPast()) {
            return null;
        }

        return (int) ceil(now()->diffInDays($debut) / 7);
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so this is statically `string` here.
     */
    private function formatDate(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
