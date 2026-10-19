<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['seance_id', 'inscription_id', 'present'])]
class Presence extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
        ];
    }

    /** @return BelongsTo<Seance, $this> */
    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    /** @return BelongsTo<Inscription, $this> */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }
}
