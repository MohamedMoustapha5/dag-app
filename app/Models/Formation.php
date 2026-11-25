<?php

namespace App\Models;

use Database\Factories\FormationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property list<string>|null $pieces_requises
 */
#[Fillable(['nom', 'description', 'programme', 'categorie', 'duree', 'prerequis', 'pieces_requises', 'image', 'statut'])]
class Formation extends Model
{
    /** @use HasFactory<FormationFactory> */
    use HasFactory, HasUuids;

    /**
     * The pieces asked when no list was set for the formation.
     *
     * @var list<string>
     */
    public const PIECES_PAR_DEFAUT = [
        "Copie de la carte nationale d'identité (CNI)",
        "Copie de l'acte de naissance",
        "Photo d'identité",
    ];

    protected function casts(): array
    {
        return [
            'pieces_requises' => 'array',
        ];
    }

    /**
     * The documents an apprenant must join to enrol in this formation.
     *
     * @return list<string>
     */
    public function piecesRequises(): array
    {
        $pieces = array_values(array_filter(
            array_map('trim', $this->pieces_requises ?? []),
            fn (string $piece) => $piece !== '',
        ));

        return $pieces === [] ? self::PIECES_PAR_DEFAUT : $pieces;
    }

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
