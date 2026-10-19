<?php

namespace App\Models;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Support\DroitsMatrix;
use Database\Factories\MembreEquipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MembreEquipeRole $role
 */
#[Fillable(['user_id', 'role', 'statut'])]
class MembreEquipe extends Model
{
    /** @use HasFactory<MembreEquipeFactory> */
    use HasFactory, HasUuids;

    protected $table = 'membres_equipe';

    protected function casts(): array
    {
        return [
            'role' => MembreEquipeRole::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Paiement, $this> */
    public function paiementsValides(): HasMany
    {
        return $this->hasMany(Paiement::class, 'valide_par_id');
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'ACTIF';
    }

    /**
     * Whether this team member may perform the given action on the given
     * data domain, per the access matrix (section 3.2). A deactivated
     * member never has any right, regardless of role.
     */
    public function peut(Donnee $donnee, Action $action): bool
    {
        if (! $this->estActif()) {
            return false;
        }

        return DroitsMatrix::niveau($this->role, $donnee)->autorise($action);
    }
}
