<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['formation_session_id', 'titre', 'date'])]
class Seance extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<FormationSession, $this> */
    public function formationSession(): BelongsTo
    {
        return $this->belongsTo(FormationSession::class);
    }

    /** @return HasMany<Presence, $this> */
    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }
}
