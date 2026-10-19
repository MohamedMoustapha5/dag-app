<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['apprenant_id', 'formation_id', 'formation_session_id', 'montant_negocie', 'statut'])]
class Inscription extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
        ];
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

    /** @return HasOne<Facture, $this> */
    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }
}
