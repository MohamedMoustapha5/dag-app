<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserType|null $type
 * @property string $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 * @property-read Client|null $client
 * @property-read Apprenant|null $apprenant
 * @property-read MembreEquipe|null $membreEquipe
 */
#[Fillable(['name', 'email', 'password', 'current_team_id', 'type', 'locale', 'notif_devis', 'notif_facture', 'notif_offres'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'type' => UserType::class,
            'notif_devis' => 'boolean',
            'notif_facture' => 'boolean',
            'notif_offres' => 'boolean',
        ];
    }

    /** @return HasOne<Client, $this> */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /** @return HasOne<Apprenant, $this> */
    public function apprenant(): HasOne
    {
        return $this->hasOne(Apprenant::class);
    }

    /** @return HasOne<MembreEquipe, $this> */
    public function membreEquipe(): HasOne
    {
        return $this->hasOne(MembreEquipe::class);
    }

    public function estMembreEquipe(): bool
    {
        return $this->type === UserType::MembreEquipe;
    }

    public function estClient(): bool
    {
        return $this->type === UserType::Client;
    }

    public function estApprenant(): bool
    {
        return $this->type === UserType::Apprenant;
    }

    /**
     * Whether this account is allowed to authenticate. A deactivated team
     * member is rejected at login (section 6.9 of the cahier des charges).
     */
    public function peutSeConnecter(): bool
    {
        return match ($this->type) {
            UserType::MembreEquipe => $this->membreEquipe?->estActif() ?? false,
            default => true,
        };
    }
}
