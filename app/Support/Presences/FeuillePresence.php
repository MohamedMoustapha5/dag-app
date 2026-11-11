<?php

namespace App\Support\Presences;

use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The attendance sheet of a formation session, as the DAG team takes it:
 * who is on it (confirmed seats only), where each séance stands (to come,
 * to take, partly taken, taken) and how assiduous each apprenant is.
 */
class FeuillePresence
{
    /**
     * The apprenants on the sheet, by name.
     *
     * @return Collection<int, Inscription>
     */
    public static function inscrits(FormationSession $session): Collection
    {
        return $session->inscriptions()
            ->whereIn('statut', Presence::INSCRIPTIONS_POINTEES)
            ->with('apprenant.user')
            ->get()
            ->sortBy(fn (Inscription $inscription) => mb_strtolower($inscription->apprenant->user->name))
            ->values();
    }

    /**
     * Attendance is taken on the day of the séance or after, never before.
     */
    public static function peutEtrePointee(Seance $seance): bool
    {
        return Carbon::parse($seance->date)->startOfDay()->lte(Carbon::today());
    }

    /**
     * Where the sheet of one séance stands.
     *
     * @param  Collection<int, Inscription>  $inscrits
     * @return array{etat: 'a_venir'|'a_pointer'|'incomplete'|'pointee', pointes: int, inscrits: int, presents: int, absents: int}
     */
    public static function resume(Seance $seance, Collection $inscrits): array
    {
        $ids = $inscrits->pluck('id')->all();
        $presences = $seance->presences->filter(fn (Presence $presence) => in_array($presence->inscription_id, $ids, true));
        $pointes = $presences->count();

        $etat = match (true) {
            ! self::peutEtrePointee($seance) => 'a_venir',
            $pointes === 0 => 'a_pointer',
            $pointes < count($ids) => 'incomplete',
            default => 'pointee',
        };

        return [
            'etat' => $etat,
            'pointes' => $pointes,
            'inscrits' => count($ids),
            'presents' => $presences->where('present', true)->count(),
            'absents' => $presences->where('present', false)->count(),
        ];
    }

    /**
     * How many séances already held still miss attendance, read from the
     * session's loaded `inscriptions` and `seances.presences`.
     */
    public static function seancesAPointer(FormationSession $session): int
    {
        $inscrits = $session->inscriptions
            ->whereIn('statut', Presence::INSCRIPTIONS_POINTEES)
            ->values();

        return $session->seances
            ->filter(fn (Seance $seance) => in_array(self::resume($seance, $inscrits)['etat'], ['a_pointer', 'incomplete'], true))
            ->count();
    }

    /**
     * How assiduous an apprenant is over the séances already held: the rate
     * only counts the séances where attendance was recorded for them, and
     * never a record dated after today.
     *
     * @param  Collection<int, Seance>  $seances  with their presences loaded
     * @return array{presents: int, retards: int, absents: int, excuses: int, pointees: int, taux: int|null}
     */
    public static function assiduite(Inscription $inscription, Collection $seances): array
    {
        $presences = $seances
            ->filter(fn (Seance $seance) => self::peutEtrePointee($seance))
            ->map(fn (Seance $seance) => $seance->presences->firstWhere('inscription_id', $inscription->id))
            ->filter();

        $compter = fn (string $statut): int => $presences->where('statut', $statut)->count();
        $pointees = $presences->count();
        $presents = $presences->where('present', true)->count();

        return [
            'presents' => $compter('PRESENT'),
            'retards' => $compter('RETARD'),
            'absents' => $compter('ABSENT'),
            'excuses' => $compter('EXCUSE'),
            'pointees' => $pointees,
            'taux' => $pointees === 0 ? null : (int) round($presents / $pointees * 100),
        ];
    }

    /**
     * A name typed by an apprenant starting with =, +, - or @ would run as a
     * formula once the export is opened in a spreadsheet: it stays text.
     */
    public static function celluleSure(string $valeur): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valeur) === 1 ? "'".$valeur : $valeur;
    }
}
