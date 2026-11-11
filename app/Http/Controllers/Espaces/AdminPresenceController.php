<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Support\Presences\FeuillePresence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance taken by the DAG team, séance by séance: the sheet of a séance,
 * saving it, and the attendance of a whole session as a spreadsheet.
 */
class AdminPresenceController extends Controller
{
    use SharesAdminEspaceProps;

    public function show(Request $request, Seance $seance): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $seance->load(['formationSession.formation', 'presences.pointeePar']);
        $session = $seance->formationSession;
        $seances = $session->seances()->orderBy('date')->orderBy('created_at')->get(['id', 'titre', 'date']);
        $position = $seances->search(fn (Seance $autre) => $autre->id === $seance->id);
        $presences = $seance->presences->keyBy('inscription_id');
        $inscrits = FeuillePresence::inscrits($session);
        $dernier = $seance->presences->whereNotNull('pointee_at')->sortByDesc('pointee_at')->first();

        return Inertia::render('espaces/admin-seance-presences', [
            ...$this->adminEspaceProps($membre),
            'session' => [
                'id' => $session->id,
                'formationNom' => $session->formation->nom,
                'ville' => $session->ville,
                'formateur' => $session->formateur,
                'lieu' => $session->lieu,
            ],
            'seance' => [
                'id' => $seance->id,
                'titre' => $seance->titre ?? 'Séance '.((int) $position + 1),
                'date' => Carbon::parse($seance->date)->toDateString(),
                'numero' => (int) $position + 1,
                'total' => $seances->count(),
                'pointable' => FeuillePresence::peutEtrePointee($seance),
            ],
            'precedente' => $this->voisine($seances, $position === false ? null : $position - 1),
            'suivante' => $this->voisine($seances, $position === false ? null : $position + 1),
            'apprenants' => $inscrits->map(function (Inscription $inscription) use ($presences) {
                $presence = $presences->get($inscription->id);

                return [
                    'inscriptionId' => $inscription->id,
                    'nom' => $inscription->apprenant->user->name,
                    'email' => $inscription->apprenant->user->email,
                    'statut' => $presence?->statut,
                    'commentaire' => $presence?->commentaire,
                ];
            })->values(),
            'statuts' => collect(Presence::STATUTS)->map(fn (string $libelle, string $valeur) => ['valeur' => $valeur, 'libelle' => $libelle])->values(),
            'dernierPointage' => $dernier === null ? null : [
                'par' => $dernier->pointeePar?->name,
                'le' => $dernier->pointee_at?->toIso8601String(),
            ],
            'peutPointer' => $membre->peut(Donnee::Formations, Action::Modifier),
        ]);
    }

    public function update(Request $request, Seance $seance): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Modifier), 403);

        if (! FeuillePresence::peutEtrePointee($seance)) {
            throw ValidationException::withMessages([
                'presences' => "Cette séance n'a pas encore eu lieu : la présence se pointe le jour même ou après.",
            ]);
        }

        $inscrits = FeuillePresence::inscrits($seance->formationSession);

        $data = $request->validate([
            'presences' => ['required', 'array', 'min:1'],
            'presences.*.inscription_id' => ['required', 'string', 'distinct', Rule::in($inscrits->pluck('id')->all())],
            'presences.*.statut' => ['required', Rule::in(array_keys(Presence::STATUTS))],
            'presences.*.commentaire' => ['nullable', 'string', 'max:255'],
        ], [
            'presences.*.inscription_id.in' => "Cet apprenant n'est pas sur la feuille de présence de la session.",
            'presences.*.statut.required' => 'Choisissez un statut pour chaque apprenant.',
        ]);

        /** @var array<string, int> $parStatut */
        $parStatut = [];

        DB::transaction(function () use ($data, $seance, $membre, &$parStatut) {
            foreach ($data['presences'] as $ligne) {
                $parStatut[$ligne['statut']] = ($parStatut[$ligne['statut']] ?? 0) + 1;

                $presence = Presence::firstOrNew([
                    'seance_id' => $seance->id,
                    'inscription_id' => $ligne['inscription_id'],
                ]);
                $presence->fill([
                    'statut' => $ligne['statut'],
                    'commentaire' => filled($ligne['commentaire'] ?? null) ? trim($ligne['commentaire']) : null,
                ]);

                if (! $presence->exists || $presence->isDirty()) {
                    $presence->pointee_par = $membre->id;
                    $presence->pointee_at = now();
                    $presence->save();
                }
            }
        });

        $seance->load('presences');
        $resume = FeuillePresence::resume($seance, $inscrits);
        $detail = collect(Presence::STATUTS)
            ->map(fn (string $libelle, string $statut) => isset($parStatut[$statut]) ? mb_strtolower($libelle).' : '.$parStatut[$statut] : null)
            ->filter()
            ->implode(', ');

        ActivityLog::record(
            $membre,
            'a pointé la présence',
            $seance,
            ($seance->titre ?? 'Séance').' du '.Carbon::parse($seance->date)->format('d/m/Y')." — {$seance->formationSession->formation->nom} ({$detail}).",
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $resume['etat'] === 'pointee'
                ? "Présence enregistrée : {$resume['presents']} présent(s) sur {$resume['inscrits']}."
                : 'Présence enregistrée. Il reste '.($resume['inscrits'] - $resume['pointes']).' apprenant(s) à pointer.',
        ]);

        return back();
    }

    public function export(Request $request, FormationSession $formationSession): StreamedResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Telecharger), 403);

        $formationSession->load(['formation', 'seances' => fn ($query) => $query->orderBy('date')->with('presences')]);
        $seances = $formationSession->seances;
        $inscrits = FeuillePresence::inscrits($formationSession);

        $nomFichier = 'presences-'.str($formationSession->formation->nom.'-'.$formationSession->ville)->slug().'.csv';

        return response()->streamDownload(function () use ($seances, $inscrits) {
            $out = fopen('php://output', 'w');
            abort_if($out === false, 500, 'Impossible de générer le fichier CSV.');

            // Excel only reads accents right with the byte order mark.
            fwrite($out, "\u{FEFF}");

            fputcsv($out, [
                'Apprenant',
                'E-mail',
                ...$seances->values()->map(fn (Seance $seance, int $index) => ($seance->titre ?? 'Séance '.($index + 1)).' ('.Carbon::parse($seance->date)->format('d/m/Y').')'),
                'Présences',
                'Retards',
                'Absences',
                'Absences excusées',
                'Assiduité',
            ], ';');

            foreach ($inscrits as $inscription) {
                $assiduite = FeuillePresence::assiduite($inscription, $seances);

                fputcsv($out, [
                    FeuillePresence::celluleSure($inscription->apprenant->user->name),
                    FeuillePresence::celluleSure($inscription->apprenant->user->email),
                    ...$seances->map(function (Seance $seance) use ($inscription) {
                        $statut = $seance->presences->firstWhere('inscription_id', $inscription->id)?->statut;

                        return $statut === null ? '' : Presence::STATUTS[$statut];
                    }),
                    $assiduite['presents'],
                    $assiduite['retards'],
                    $assiduite['absents'],
                    $assiduite['excuses'],
                    $assiduite['taux'] === null ? '' : $assiduite['taux'].' %',
                ], ';');
            }

            fclose($out);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Collection<int, Seance>  $seances
     * @return array{id: string, titre: string, date: string}|null
     */
    private function voisine(Collection $seances, ?int $index): ?array
    {
        $voisine = $index === null ? null : $seances->get($index);

        return $voisine === null ? null : [
            'id' => $voisine->id,
            'titre' => $voisine->titre ?? 'Séance '.($index + 1),
            'date' => Carbon::parse($voisine->date)->toDateString(),
        ];
    }
}
