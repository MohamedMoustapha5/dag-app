<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['facture_id', 'montant', 'reference_transaction', 'moyen', 'statut', 'justificatif_path', 'valide_par_id', 'date_paiement'])]
class Paiement extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'datetime',
        ];
    }

    /** @return BelongsTo<Facture, $this> */
    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'valide_par_id');
    }
}
