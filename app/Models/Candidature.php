<?php

namespace App\Models;

use Database\Factories\CandidatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['apprenant_id', 'offre_id', 'statut'])]
class Candidature extends Model
{
    /** @use HasFactory<CandidatureFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /** @return BelongsTo<OffreEmploi, $this> */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(OffreEmploi::class, 'offre_id');
    }

    /** @return HasOne<Contrat, $this> */
    public function contrat(): HasOne
    {
        return $this->hasOne(Contrat::class);
    }

    /**
     * Name of the apprenant's most recent formation, shown to the
     * entreprise reviewing this candidature. Null if none.
     */
    public function formationSuivie(): ?string
    {
        return $this->apprenant->inscriptions->sortByDesc('created_at')->first()?->formation?->nom;
    }
}
