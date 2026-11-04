<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Carbon|null $date_souscription
 * @property Carbon|null $cycle_debut
 * @property Carbon|null $echeance
 */
#[Fillable(['client_id', 'entreprise_id', 'formule_id', 'montant_negocie', 'date_souscription', 'cycle_debut', 'echeance', 'statut'])]
class Abonnement extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
            'date_souscription' => 'date',
            'cycle_debut' => 'date',
            'echeance' => 'date',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<EntreprisePartenaire, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(EntreprisePartenaire::class, 'entreprise_id');
    }

    /** @return BelongsTo<Formule, $this> */
    public function formule(): BelongsTo
    {
        return $this->belongsTo(Formule::class);
    }

    /** @return HasMany<Facture, $this> */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /** @return HasOne<Contrat, $this> */
    public function contrat(): HasOne
    {
        return $this->hasOne(Contrat::class);
    }

    /**
     * Percentage of the current billing cycle already elapsed, clamped to
     * [0, 100]. Zero when the cycle dates are missing or the cycle has no
     * duration (cycle_debut === echeance).
     */
    public function progressionCycle(): int
    {
        if ($this->cycle_debut === null || $this->echeance === null) {
            return 0;
        }

        $debut = $this->cycle_debut;
        $fin = $this->echeance;

        $dureeCycle = $debut->diffInDays($fin);

        if ($dureeCycle <= 0) {
            return 0;
        }

        $ecoule = $debut->diffInDays(Carbon::now(), true);

        return min(100, max(0, (int) round($ecoule / $dureeCycle * 100)));
    }

    /** Days before the end of a cycle from which a renewal is offered to the client. */
    public const JOURS_AVANT_RENOUVELLEMENT = 30;

    /** Days before the end of a cycle from which the subscription is flagged as ending soon. */
    public const JOURS_ALERTE = 15;

    private const MOIS_COURTS = ['Janv', 'Févr', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];

    /** Statuses of a renewal request that is still waiting for the team to renew the subscription. */
    private const STATUTS_RENOUVELLEMENT_OUVERT = ['EN_ETUDE', 'DEVIS_ENVOYE', 'PAYEE'];

    /**
     * What the team has to watch: renewal requests waiting for a renewed
     * subscription, subscriptions ending soon and subscriptions past their end
     * date but still marked active.
     *
     * @return array{renouvellementsDemandes: int, bientot: int, expires: int}
     */
    public static function resumeSurveillance(): array
    {
        $resume = ['renouvellementsDemandes' => 0, 'bientot' => 0, 'expires' => 0];

        foreach (self::query()->with('entreprise')->where('statut', 'ACTIF')->get() as $abonnement) {
            $etat = $abonnement->etatEcheance();

            if ($etat === 'bientot') {
                $resume['bientot']++;
            }

            if ($etat === 'expire') {
                $resume['expires']++;
            }

            if ($abonnement->demandeRenouvellementEnCours() !== null) {
                $resume['renouvellementsDemandes']++;
            }
        }

        return $resume;
    }

    /**
     * Whole days left before the end of the cycle (negative once past), or
     * null when no end date is set.
     */
    public function joursRestants(): ?int
    {
        if ($this->echeance === null) {
            return null;
        }

        return (int) Carbon::today()->diffInDays(Carbon::parse($this->echeance)->startOfDay(), false);
    }

    /**
     * Display state derived from the status and the end date: actif, bientot
     * (ending within JOURS_ALERTE days), expire (past its end date but still
     * marked active), suspendu or termine.
     */
    public function etatEcheance(): string
    {
        if ($this->statut === 'TERMINE') {
            return 'termine';
        }

        if ($this->statut === 'SUSPENDU') {
            return 'suspendu';
        }

        $jours = $this->joursRestants();

        if ($jours !== null && $jours < 0) {
            return 'expire';
        }

        if ($jours !== null && $jours <= self::JOURS_ALERTE) {
            return 'bientot';
        }

        return 'actif';
    }

    /** Whether the client may ask for a renewal now. */
    public function peutEtreRenouvele(): bool
    {
        if ($this->formule_id === null || ! in_array($this->statut, ['ACTIF', 'TERMINE'], true)) {
            return false;
        }

        if ($this->statut === 'TERMINE') {
            return true;
        }

        $jours = $this->joursRestants();

        return $jours !== null && $jours <= self::JOURS_AVANT_RENOUVELLEMENT;
    }

    /**
     * The renewal request the client already sent for this subscription and
     * that the team has not yet turned into a renewed subscription.
     */
    public function demandeRenouvellementEnCours(): ?Demande
    {
        if ($this->formule_id === null) {
            return null;
        }

        $clientId = $this->client_id ?? ($this->entreprise_id !== null ? $this->entreprise->client_id : null);

        return Demande::query()
            ->where('formule_id', $this->formule_id)
            ->where('description', 'like', 'Renouvellement%')
            ->whereIn('statut', self::STATUTS_RENOUVELLEMENT_OUVERT)
            ->where(function ($query) use ($clientId) {
                $query->where('entreprise_id', $this->entreprise_id ?? '');

                if ($clientId !== null) {
                    $query->orWhere('client_id', $clientId);
                }
            })
            ->latest()
            ->first();
    }

    /**
     * The months of the billing cycle, each with its share of the cycle and
     * how far it has elapsed, plus the status of the invoice due in that
     * month (PAYEE, EN_ATTENTE, PARTIELLEMENT_PAYEE or null without invoice).
     *
     * @return list<array{label: string, annee: int, poids: int, avancement: int, courant: bool, facture: string|null}>
     */
    public function moisDuCycle(): array
    {
        if ($this->cycle_debut === null || $this->echeance === null) {
            return [];
        }

        $debut = Carbon::parse($this->cycle_debut)->startOfDay();
        $fin = Carbon::parse($this->echeance)->startOfDay();

        if ($fin->lessThanOrEqualTo($debut)) {
            return [];
        }

        $aujourdhui = Carbon::today();
        $factures = $this->factures;
        $segments = [];
        $curseur = $debut->copy();

        while ($curseur->lessThan($fin) && count($segments) < 36) {
            $suivant = $curseur->copy()->addMonthNoOverflow();
            $finSegment = $suivant->greaterThan($fin) ? $fin->copy() : $suivant;
            $poids = max(1, (int) $curseur->diffInDays($finSegment));
            $ecoule = (int) min($poids, max(0, $curseur->diffInDays($aujourdhui, false)));

            $segments[] = [
                'label' => self::MOIS_COURTS[$curseur->month - 1],
                'annee' => $curseur->year,
                'poids' => $poids,
                'avancement' => (int) round($ecoule / $poids * 100),
                'courant' => $aujourdhui->greaterThanOrEqualTo($curseur) && $aujourdhui->lessThan($finSegment),
                'facture' => self::statutFactureDuMois($factures, $curseur, $finSegment),
            ];

            $curseur = $suivant;
        }

        return $segments;
    }

    /**
     * Status of the invoice whose due date falls in [debut, fin), preferring a
     * paid one, or null when none is due in that period.
     *
     * @param  Collection<int, Facture>  $factures
     */
    private static function statutFactureDuMois(Collection $factures, Carbon $debut, Carbon $fin): ?string
    {
        $statuts = [];

        foreach ($factures as $facture) {
            $date = Carbon::parse($facture->echeance ?? $facture->created_at)->startOfDay();

            if ($date->greaterThanOrEqualTo($debut) && $date->lessThan($fin)) {
                $statuts[] = $facture->statut;
            }
        }

        if ($statuts === []) {
            return null;
        }

        return in_array('PAYEE', $statuts, true) ? 'PAYEE' : $statuts[0];
    }
}
