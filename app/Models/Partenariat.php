<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Database\Factories\PartenariatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A partnership between DAG and an entreprise or an organisation. The
 * client asks for it (DEMANDE) or DAG proposes it (PROPOSITION); once
 * accepted it goes through a partnership contract (CONTRAT) the client
 * signs and DAG validates, and is then ACTIF from debut to fin included.
 *
 * @property string $id
 * @property string $reference
 * @property string $client_id
 * @property string $origine
 * @property string $statut
 * @property list<string> $domaines
 * @property string|null $message
 * @property int|null $duree_mois
 * @property Carbon|null $debut
 * @property Carbon|null $fin
 * @property string|null $contrat_id
 * @property string|null $motif_refus
 * @property Carbon|null $traite_le
 */
#[Fillable(['client_id', 'origine', 'statut', 'domaines', 'message', 'duree_mois', 'debut', 'fin', 'contrat_id', 'motif_refus', 'traite_par_id', 'traite_le'])]
class Partenariat extends Model
{
    /** @use HasFactory<PartenariatFactory> */
    use GeneratesReference, HasFactory, HasUuids;

    /** @var array<string, string> */
    public const DOMAINES = [
        'RECRUTEMENT' => 'Recrutement et stages des talents formés par DAG',
        'FORMATION' => 'Formation et montée en compétences des équipes',
        'DIGITAL' => 'Prestations digitales (site, marketing, communication)',
        'EVENEMENTS' => 'Événements, conférences et visibilité commune',
    ];

    /** Durations DAG can grant, in months. */
    public const DUREES = [self::DUREE_INDETERMINEE, 6, 12, 24];

    /** An open-ended partnership, tacitly renewed: it has no end date. */
    public const DUREE_INDETERMINEE = 0;

    public static function referencePrefix(): string
    {
        return 'DAG-PAR';
    }

    protected function casts(): array
    {
        return [
            'domaines' => 'array',
            'duree_mois' => 'integer',
            'debut' => 'date',
            'fin' => 'date',
            'traite_le' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Contrat, $this> */
    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'traite_par_id');
    }

    /**
     * Partnerships in force today.
     *
     * @param  Builder<Partenariat>  $query
     * @return Builder<Partenariat>
     */
    #[Scope]
    protected function enVigueur(Builder $query): Builder
    {
        return $query->where('statut', 'ACTIF')->where(fn (Builder $periode) => $periode
            ->whereNull('fin')
            ->orWhereDate('fin', '>=', now()->toDateString()));
    }

    /**
     * Partnerships still running or under way: a new one cannot be opened
     * next to them.
     *
     * @param  Builder<Partenariat>  $query
     * @return Builder<Partenariat>
     */
    #[Scope]
    protected function ouverts(Builder $query): Builder
    {
        return $query->where(fn (Builder $ouvert) => $ouvert
            ->whereIn('statut', ['EN_ETUDE', 'CONTRAT'])
            ->orWhere(fn (Builder $actif) => $actif->where('statut', 'ACTIF')->where(fn (Builder $periode) => $periode
                ->whereNull('fin')
                ->orWhereDate('fin', '>=', now()->toDateString()))));
    }

    public function estEnVigueur(): bool
    {
        return $this->statut === 'ACTIF' && ($this->fin === null || $this->fin->gte(today()));
    }

    public function estDureeIndeterminee(): bool
    {
        return $this->duree_mois === self::DUREE_INDETERMINEE;
    }

    /**
     * The step shown to the client and to the team. During the contract
     * stage, the contract's own status tells where it stands.
     */
    public function etape(): string
    {
        if ($this->statut === 'ACTIF') {
            return $this->estEnVigueur() ? 'ACTIF' : 'TERMINE';
        }

        if ($this->statut !== 'CONTRAT') {
            return $this->statut;
        }

        return match ($this->contrat?->statut) {
            'ENVOYE' => 'A_SIGNER',
            'SIGNE' => 'A_VALIDER',
            default => 'EN_REDACTION',
        };
    }

    public function libelleDomaines(): string
    {
        $libelles = collect($this->domaines ?? [])
            ->map(fn (string $domaine) => self::DOMAINES[$domaine] ?? null)
            ->filter()
            ->map(fn (string $libelle) => mb_strtolower(mb_substr($libelle, 0, 1)).mb_substr($libelle, 1))
            ->values();

        return $libelles->isEmpty() ? 'à définir entre les parties' : $libelles->implode(' ; ');
    }

    public function libelleDuree(): string
    {
        return match (true) {
            $this->duree_mois === null => '—',
            $this->estDureeIndeterminee() => 'durée indéterminée',
            $this->duree_mois % 12 === 0 => ($this->duree_mois / 12).' an'.($this->duree_mois > 12 ? 's' : ''),
            default => "{$this->duree_mois} mois",
        };
    }

    /**
     * « pour une durée de 2 ans », « pour une durée indéterminée ».
     */
    public function pourUneDuree(): string
    {
        return $this->estDureeIndeterminee() ? 'pour une durée indéterminée' : "pour une durée de {$this->libelleDuree()}";
    }

    /**
     * The duration as shown in the espaces: « Durée : 2 ans », « Durée indéterminée ».
     */
    public function dureeAffichee(): ?string
    {
        return match (true) {
            $this->duree_mois === null => null,
            $this->estDureeIndeterminee() => 'Durée indéterminée',
            default => "Durée : {$this->libelleDuree()}",
        };
    }
}
