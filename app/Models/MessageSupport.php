<?php

namespace App\Models;

use Database\Factories\MessageSupportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_support_id', 'auteur', 'membre_equipe_id', 'contenu'])]
class MessageSupport extends Model
{
    /** @use HasFactory<MessageSupportFactory> */
    use HasFactory, HasUuids;

    protected $table = 'messages_support';

    /** @return BelongsTo<TicketSupport, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TicketSupport::class, 'ticket_support_id');
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'membre_equipe_id');
    }
}
