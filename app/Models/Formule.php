<?php

namespace App\Models;

use Database\Factories\FormuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'contenu', 'services_inclus', 'prix_affiche', 'populaire', 'statut'])]
class Formule extends Model
{
    /** @use HasFactory<FormuleFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'services_inclus' => 'array',
            'populaire' => 'boolean',
        ];
    }

    /** @return HasMany<Demande, $this> */
    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    /** @return HasMany<Abonnement, $this> */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }
}
