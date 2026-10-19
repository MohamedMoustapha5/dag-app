<?php

namespace App\Models;

use Database\Factories\ApprenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'date_naissance', 'telephone', 'ville', 'cv_path', 'consentement_cv'])]
class Apprenant extends Model
{
    /** @use HasFactory<ApprenantFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'consentement_cv' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Inscription, $this> */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /** @return HasMany<Candidature, $this> */
    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class);
    }

    /** @return HasMany<Attestation, $this> */
    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    /** @return HasMany<Contrat, $this> */
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }
}
