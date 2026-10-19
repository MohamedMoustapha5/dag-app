<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'type', 'contenu', 'statut'])]
class ModeleContrat extends Model
{
    use HasUuids;

    protected $table = 'modeles_contrat';

    /** @return HasMany<Contrat, $this> */
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }
}
