<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * An apprenant's enrolment in a session. It goes through: dossier submitted
 * (DOSSIER_EN_ETUDE) → dossier validated by DAG, fee billed
 * (EN_ATTENTE_PAIEMENT) → fee paid, seat confirmed (VALIDEE) → FORMEE. DAG
 * can send the dossier back for completion (DOSSIER_A_COMPLETER) or refuse
 * it (ABANDONNEE).
 *
 * @property Carbon|null $dossier_valide_at
 */
#[Fillable(['apprenant_id', 'formation_id', 'formation_session_id', 'montant_negocie', 'statut', 'motif_decision', 'dossier_valide_at'])]
class Inscription extends Model
{
    use HasUuids;

    /**
     * Statuses that hold a seat in the session: a dossier under review keeps
     * its place, so DAG never validates more apprenants than seats.
     *
     * @var list<string>
     */
    public const STATUTS_PLACE_RESERVEE = ['DOSSIER_EN_ETUDE', 'DOSSIER_A_COMPLETER', 'EN_ATTENTE_PAIEMENT', 'VALIDEE', 'FORMEE'];

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
            'dossier_valide_at' => 'datetime',
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

    /** @return HasMany<PieceInscription, $this> */
    public function pieces(): HasMany
    {
        return $this->hasMany(PieceInscription::class);
    }
}
