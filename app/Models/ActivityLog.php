<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['membre_equipe_id', 'action', 'sujet_type', 'sujet_id', 'description'])]
class ActivityLog extends Model
{
    use HasUuids;

    public $timestamps = true;

    const UPDATED_AT = null;

    /** @return BelongsTo<MembreEquipe, $this> */
    public function membreEquipe(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class);
    }

    public static function record(?MembreEquipe $auteur, string $action, ?Model $sujet = null, ?string $description = null): self
    {
        return self::create([
            'membre_equipe_id' => $auteur?->id,
            'action' => $action,
            'sujet_type' => $sujet ? class_basename($sujet) : null,
            'sujet_id' => $sujet?->getKey(),
            'description' => $description,
        ]);
    }
}
