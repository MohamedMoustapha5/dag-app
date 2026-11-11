<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document of an enrolment dossier, kept on the private disk: it is
 * personal data, only served to the DAG team through an authorized route.
 */
#[Fillable(['inscription_id', 'libelle', 'chemin', 'nom_original'])]
class PieceInscription extends Model
{
    use HasUuids;

    public const DOSSIER = 'inscriptions-pieces';

    protected $table = 'pieces_inscription';

    /** @return BelongsTo<Inscription, $this> */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }
}
