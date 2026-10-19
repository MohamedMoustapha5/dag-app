<?php

namespace App\Models;

use Database\Factories\FormationSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_debut
 * @property Carbon|null $date_fin
 */
#[Fillable(['formation_id', 'ville', 'mode', 'lieu', 'formateur', 'date_debut', 'date_fin', 'places', 'image', 'statut'])]
class FormationSession extends Model
{
    /** @use HasFactory<FormationSessionFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /** @return HasMany<Seance, $this> */
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    /** @return HasMany<Inscription, $this> */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function placesRestantes(): int
    {
        $prises = $this->inscriptions()
            ->whereIn('statut', ['EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE'])
            ->count();

        return max(0, $this->places - $prises);
    }
}
