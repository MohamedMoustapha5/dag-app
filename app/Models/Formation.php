<?php

namespace App\Models;

use Database\Factories\FormationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'description', 'programme', 'categorie', 'duree', 'prerequis', 'image', 'statut'])]
class Formation extends Model
{
    /** @use HasFactory<FormationFactory> */
    use HasFactory, HasUuids;

    /** @return HasMany<FormationSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(FormationSession::class);
    }

    /** @return HasMany<Inscription, $this> */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /** @return HasMany<Attestation, $this> */
    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    /**
     * Category badge shown on the public pages. Every formation displays one:
     * a formation created without a category falls back to a generic label
     * instead of showing an empty badge.
     */
    public function libelleCategorie(): string
    {
        return $this->categorie !== null && trim($this->categorie) !== '' ? $this->categorie : 'Formation';
    }
}
