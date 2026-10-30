<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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

        // Normalized explicitly: Larastan's schema-derived column type for
        // a `date`-cast attribute (as opposed to `datetime`) loses the
        // Carbon type here, so these are statically `string`.
        $debut = Carbon::parse($this->cycle_debut);
        $fin = Carbon::parse($this->echeance);

        $dureeCycle = $debut->diffInDays($fin);

        if ($dureeCycle <= 0) {
            return 0;
        }

        $ecoule = $debut->diffInDays(Carbon::now(), true);

        return min(100, max(0, (int) round($ecoule / $dureeCycle * 100)));
    }
}
