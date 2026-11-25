<?php

namespace App\Models;

use Database\Factories\ClientCollaborateurFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A person an Entreprise or Organisation client let into its espace. They
 * sign in with their own account and manage everything for the client,
 * except who has access (kept to the account holder).
 *
 * @property int $id
 * @property string $client_id
 * @property int $user_id
 * @property int|null $invite_par_id
 * @property Carbon|null $accepte_le
 */
#[Fillable(['client_id', 'user_id', 'invite_par_id', 'accepte_le'])]
class ClientCollaborateur extends Model
{
    /** @use HasFactory<ClientCollaborateurFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'accepte_le' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invite_par_id');
    }

    public function estActif(): bool
    {
        return $this->accepte_le !== null;
    }
}
