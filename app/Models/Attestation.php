<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reference', 'apprenant_id', 'formation_id', 'date_emission', 'fichier_pdf'])]
class Attestation extends Model
{
    use GeneratesReference, HasUuids;

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
        ];
    }

    public static function referencePrefix(): string
    {
        return 'DAG-ATT';
    }

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
}
