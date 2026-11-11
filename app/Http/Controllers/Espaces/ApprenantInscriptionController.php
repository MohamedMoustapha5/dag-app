<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\PieceInscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Support\Notifications\Notifier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantInscriptionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apprenant = $request->user('web')->apprenant;

        $inscriptions = $apprenant->inscriptions()
            ->with(['formation', 'formationSession.seances', 'facture', 'pieces'])
            ->latest()
            ->get();

        return Inertia::render('espaces/apprenant/inscriptions-index', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'inscriptions' => $inscriptions->map(fn (Inscription $inscription) => $this->mapInscription($inscription))->all(),
        ]);
    }

    /**
     * Confirmation screen of an enrolment, reached from the "S'inscrire"
     * button of the espace catalogue or of the public formation page (after
     * signing in as an apprenant when needed). It states the registration
     * fee the apprenant is about to be billed.
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
                'prix' => $formationSession->prix,
                'ouverte' => $formationSession->statut === 'OUVERTE' && $formationSession->formation->statut === 'PUBLIEE',
                'piecesRequises' => $formationSession->formation->piecesRequises(),
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

        abort_if($formationSession->prix === null, 409, "Les frais d'inscription de cette session ne sont pas encore publiés.");

        // Checked again under lock below; here it spares uploading a dossier for nothing.
        abort_if($formationSession->placesRestantes() <= 0, 409, 'Cette session est complète.');

        $piecesRequises = $formationSession->formation->piecesRequises();
        $request->validate(...$this->reglesPieces($piecesRequises, true));

        // The session row is locked while counting the seats, so two apprenants
        // enrolling at the same moment can't both take the last one. The dossier
        // under review holds the seat; the fee is only billed once DAG validates it.
        $inscription = DB::transaction(function () use ($apprenant, $formationSession): Inscription {
            $verrouillee = FormationSession::whereKey($formationSession->id)->lockForUpdate()->firstOrFail();

            abort_if($verrouillee->placesRestantes() <= 0, 409, 'Cette session est complète.');
            abort_if($this->dejaInscrit($apprenant, $verrouillee), 409, 'Vous êtes déjà inscrit à cette session.');

            return Inscription::create([
                'apprenant_id' => $apprenant->id,
                'formation_id' => $verrouillee->formation_id,
                'formation_session_id' => $verrouillee->id,
                'montant_negocie' => $verrouillee->prix,
                'statut' => 'DOSSIER_EN_ETUDE',
            ]);
        });

        $this->enregistrerPieces($request, $inscription, $piecesRequises);

        Notifier::alerterEquipe(
            Donnee::Formations,
            Action::Valider,
            'inscription.nouvelle',
            "Dossier d'inscription à examiner — {$formationSession->formation->nom}",
            "{$apprenant->user->name} a déposé son dossier pour la session de {$formationSession->ville}. Vérifiez les pièces puis validez-le pour lui ouvrir le paiement.",
            route('espace-admin.sessions.show', $formationSession),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Dossier envoyé : l'équipe DAG l'examine et vous prévient. Vous réglerez les frais d'inscription une fois le dossier validé.",
        ]);

        return to_route('espace-apprenant.inscriptions');
    }

    /**
     * The apprenant replaces the pieces DAG asked to complete; the dossier
     * goes back to review with the same seat.
     */
    public function completer(Request $request, Inscription $inscription): RedirectResponse
    {
        $apprenant = $request->user('web')->apprenant;

        abort_unless($inscription->apprenant_id === $apprenant->id, 404);
        abort_unless($inscription->statut === 'DOSSIER_A_COMPLETER', 409, "Ce dossier n'est pas à compléter.");

        $inscription->load(['formation', 'formationSession', 'pieces']);
        $piecesRequises = $inscription->formation->piecesRequises();
        $deposees = $inscription->pieces->pluck('libelle')->all();

        // A piece never joined yet is mandatory; one already joined may be replaced.
        $manquantes = array_filter($piecesRequises, fn (string $libelle) => ! in_array($libelle, $deposees, true));
        [$regles, $messages] = $this->reglesPieces($piecesRequises, false, array_keys($manquantes));
        $request->validate([...$regles, 'pieces' => ['required', 'array', 'min:1']], [
            ...$messages,
            'pieces.required' => 'Joignez au moins une pièce corrigée.',
        ]);

        $this->enregistrerPieces($request, $inscription, $piecesRequises);

        $inscription->update(['statut' => 'DOSSIER_EN_ETUDE', 'motif_decision' => null]);

        Notifier::alerterEquipe(
            Donnee::Formations,
            Action::Valider,
            'inscription.nouvelle',
            "Dossier complété — {$inscription->formation->nom}",
            "{$apprenant->user->name} a complété son dossier d'inscription. Il est de nouveau à examiner.",
            $inscription->formation_session_id !== null ? route('espace-admin.sessions.show', $inscription->formation_session_id) : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Dossier complété : l'équipe DAG l'examine à nouveau."]);

        return to_route('espace-apprenant.inscriptions');
    }

    /**
     * One file per required piece, sent as pieces[<index of the piece>].
     *
     * @param  list<string>  $piecesRequises
     * @param  list<int>|null  $obligatoires  indexes that must be sent (all when null)
     * @return array{0: array<string, list<string>>, 1: array<string, string>}
     */
    private function reglesPieces(array $piecesRequises, bool $toutes, ?array $obligatoires = null): array
    {
        $regles = ['pieces' => ['array']];
        $messages = [];

        foreach ($piecesRequises as $index => $libelle) {
            $requise = $toutes || in_array($index, $obligatoires ?? [], true);
            $regles["pieces.{$index}"] = [$requise ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $messages["pieces.{$index}.required"] = "Joignez la pièce : {$libelle}.";
            $messages["pieces.{$index}.mimes"] = "« {$libelle} » doit être un PDF ou une image (JPG, PNG).";
            $messages["pieces.{$index}.max"] = "« {$libelle} » ne doit pas dépasser 5 Mo.";
            $messages["pieces.{$index}.uploaded"] = "« {$libelle} » n'a pas pu être envoyée : vérifiez qu'elle fait moins de 5 Mo.";
        }

        return [$regles, $messages];
    }

    /**
     * Stores the sent pieces on the private disk, replacing a piece of the
     * same name already joined to the dossier.
     *
     * @param  list<string>  $piecesRequises
     */
    private function enregistrerPieces(Request $request, Inscription $inscription, array $piecesRequises): void
    {
        foreach ($piecesRequises as $index => $libelle) {
            $fichier = $request->file("pieces.{$index}");

            if ($fichier === null) {
                continue;
            }

            $chemin = $fichier->store(PieceInscription::DOSSIER.'/'.$inscription->id);
            abort_if($chemin === false, 500, "La pièce « {$libelle} » n'a pas pu être enregistrée.");

            $ancienne = $inscription->pieces()->where('libelle', $libelle)->first();

            if ($ancienne !== null) {
                Storage::disk('local')->delete($ancienne->chemin);
                $ancienne->update(['chemin' => $chemin, 'nom_original' => $fichier->getClientOriginalName()]);

                continue;
            }

            $inscription->pieces()->create([
                'libelle' => $libelle,
                'chemin' => $chemin,
                'nom_original' => $fichier->getClientOriginalName(),
            ]);
        }
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
            'fraisInscription' => $inscription->facture->montant ?? $inscription->montant_negocie,
            'paiement' => $inscription->facture?->statut,
            'motif' => $inscription->motif_decision,
            'piecesRequises' => $inscription->formation->piecesRequises(),
            'piecesDeposees' => $inscription->pieces->map(fn (PieceInscription $piece) => [
                'libelle' => $piece->libelle,
                'nomOriginal' => $piece->nom_original,
            ])->values()->all(),
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
