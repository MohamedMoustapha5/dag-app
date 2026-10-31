<?php

namespace App\Models;

use Database\Factories\LigneTarifaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['categorie', 'categorie_ordre', 'prestation', 'tarif', 'ordre'])]
class LigneTarifaire extends Model
{
    /** @use HasFactory<LigneTarifaireFactory> */
    use HasFactory, HasUuids;

    protected $table = 'lignes_tarifaires';
}
