<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $pointee_at
 */
#[Fillable(['seance_id', 'inscription_id', 'present', 'statut', 'commentaire', 'pointee_par', 'pointee_at'])]
class Presence extends Model
{
    use HasUuids;

    /**
     * Attendance statuses, in the order the team picks them.
     *
     * @var array<string, string>
     */
    public const STATUTS = [
        'PRESENT' => 'Présent',
        'RETARD' => 'En retard',
        'ABSENT' => 'Absent',
        'EXCUSE' => 'Excusé',
    ];

    /**
     * The statuses that count as having attended the séance.
     */
    public const STATUTS_PRESENTS = ['PRESENT', 'RETARD'];

    /**
     * Only enrolments with a confirmed seat are on the attendance sheet.
     */
    public const INSCRIPTIONS_POINTEES = ['VALIDEE', 'FORMEE'];

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
            'pointee_at' => 'datetime',
        ];
    }

    /**
     * Keeps `present` and `statut` in step: the status wins when it is set,
     * otherwise it is derived from `present` (older records, seeders).
     */
    protected static function booted(): void
    {
        static::saving(function (Presence $presence): void {
            // Read raw: before its first save a record may have no status yet.
            $statut = $presence->getAttributes()['statut'] ?? null;

            if ($statut !== null && $presence->isDirty('statut')) {
                $presence->present = in_array($statut, self::STATUTS_PRESENTS, true);
            } elseif ($statut === null || $presence->isDirty('present')) {
                $presence->statut = $presence->present ? 'PRESENT' : 'ABSENT';
            }
        });
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

    /** @return BelongsTo<MembreEquipe, $this> */
    public function pointeePar(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'pointee_par');
    }
}
