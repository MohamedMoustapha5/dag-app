<?php

namespace App\Models;

use App\Concerns\RevoqueSessionsAuChangementDeMotDePasse;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Support\DroitsMatrix;
use Database\Factories\MembreEquipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A DAG staff account. It authenticates on its own `admin` guard and table,
 * fully separate from the public `users` table (clients and apprenants).
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property MembreEquipeRole $role
 * @property string $statut
 */
#[Fillable(['name', 'email', 'password', 'role', 'statut'])]
#[Hidden(['password', 'remember_token'])]
class MembreEquipe extends Authenticatable
{
    /** @use HasFactory<MembreEquipeFactory> */
    use HasFactory, HasUuids, Notifiable, RevoqueSessionsAuChangementDeMotDePasse;

    protected $table = 'membres_equipe';

    protected function casts(): array
    {
        return [
            'role' => MembreEquipeRole::class,
            'password' => 'hashed',
        ];
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
     * data domain, per the (editable) access matrix. A deactivated member
     * never has any right, regardless of role.
     */
    public function peut(Donnee $donnee, Action $action): bool
    {
        if (! $this->estActif()) {
            return false;
        }

        return DroitsMatrix::niveau($this->role, $donnee)->autorise($action);
    }
}
