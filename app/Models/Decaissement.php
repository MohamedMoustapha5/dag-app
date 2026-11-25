<?php

namespace App\Models;

use Database\Factories\DecaissementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money withdrawn by DAG from its online payment provider balance to a
 * mobile money account (retrait).
 *
 * @property Carbon|null $date_execution
 */
#[Fillable(['montant', 'moyen', 'telephone', 'beneficiaire', 'motif', 'statut', 'reference_transaction', 'motif_echec', 'demande_par_id', 'date_execution'])]
class Decaissement extends Model
{
    /** @use HasFactory<DecaissementFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_execution' => 'datetime',
        ];
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'demande_par_id');
    }
}
