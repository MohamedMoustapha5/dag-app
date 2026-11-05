<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Database\Factories\TicketSupportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A support request and its thread. OUVERT means the team has to answer,
 * REPONDU that the requester has the last word, CLOS that it is settled.
 *
 * @property Carbon|null $dernier_message_at
 */
#[Fillable(['reference', 'user_id', 'origine', 'nom', 'email', 'categorie', 'sujet', 'statut', 'dernier_message_at'])]
class TicketSupport extends Model
{
    /** @use HasFactory<TicketSupportFactory> */
    use GeneratesReference, HasFactory, HasUuids;

    protected $table = 'tickets_support';

    /** @var array<string, string> */
    public const CATEGORIES = [
        'FACTURATION' => 'Facturation & paiement',
        'CONTRAT' => 'Contrat',
        'DEMANDE' => 'Suivi d\'une demande',
        'FORMATION' => 'Formation & inscription',
        'COMPTE' => 'Mon compte',
        'AUTRE' => 'Autre question',
    ];

    /** @var array<string, string> */
    public const STATUTS = [
        'OUVERT' => 'En attente de DAG',
        'REPONDU' => 'Réponse reçue',
        'CLOS' => 'Clôturé',
    ];

    protected function casts(): array
    {
        return [
            'dernier_message_at' => 'datetime',
        ];
    }

    public static function referencePrefix(): string
    {
        return 'DAG-S';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<MessageSupport, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MessageSupport::class)->oldest();
    }

    /**
     * Adds a message to the thread and moves the ticket to the status that
     * follows it: the team's answer awaits the requester, a requester's
     * message (even on a closed ticket) awaits the team.
     */
    public function ajouterMessage(string $contenu, ?MembreEquipe $membre = null): MessageSupport
    {
        $message = $this->messages()->create([
            'auteur' => $membre === null ? 'DEMANDEUR' : 'EQUIPE',
            'membre_equipe_id' => $membre?->id,
            'contenu' => $contenu,
        ]);

        $this->update([
            'statut' => $membre === null ? 'OUVERT' : 'REPONDU',
            'dernier_message_at' => $message->created_at,
        ]);

        return $message;
    }
}
