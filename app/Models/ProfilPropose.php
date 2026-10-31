<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['entreprise_id', 'apprenant_id', 'membre_equipe_id', 'descriptif', 'statut'])]
class ProfilPropose extends Model
{
    use HasUuids;

    protected $table = 'profils_proposes';

    /** @return BelongsTo<EntreprisePartenaire, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(EntreprisePartenaire::class, 'entreprise_id');
    }

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function membreEquipe(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class);
    }
}
