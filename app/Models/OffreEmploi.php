<?php

namespace App\Models;

use Database\Factories\OffreEmploiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_limite
 */
#[Fillable(['entreprise_id', 'titre', 'description', 'type_contrat', 'duree', 'competences', 'ville', 'date_limite', 'statut'])]
class OffreEmploi extends Model
{
    /** @use HasFactory<OffreEmploiFactory> */
    use HasFactory, HasUuids;

    protected $table = 'offres_emploi';

    protected function casts(): array
    {
        return [
            'date_limite' => 'date',
        ];
    }

    /**
     * @param  Builder<OffreEmploi>  $query
     * @return Builder<OffreEmploi>
     */
    #[Scope]
    protected function publiees(Builder $query): Builder
    {
        return $query->where('statut', 'PUBLIEE')
            ->where(fn ($q) => $q->whereNull('date_limite')->orWhereDate('date_limite', '>=', now()));
    }

    /** @return BelongsTo<EntreprisePartenaire, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(EntreprisePartenaire::class, 'entreprise_id');
    }

    /** @return HasMany<Candidature, $this> */
    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class, 'offre_id');
    }
}
