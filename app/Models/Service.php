<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'description', 'categorie', 'fournisseur', 'image', 'prix', 'statut'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    #[Scope]
    protected function publies(Builder $query): Builder
    {
        return $query->where('statut', 'PUBLIE');
    }

    /** @return HasMany<Demande, $this> */
    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }
}
