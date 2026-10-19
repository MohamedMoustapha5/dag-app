<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['client_id', 'entreprise_id', 'formule_id', 'montant_negocie', 'date_souscription', 'cycle_debut', 'echeance', 'statut'])]
class Abonnement extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
            'date_souscription' => 'date',
            'cycle_debut' => 'date',
            'echeance' => 'date',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<EntreprisePartenaire, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(EntreprisePartenaire::class, 'entreprise_id');
    }

    /** @return BelongsTo<Formule, $this> */
    public function formule(): BelongsTo
    {
        return $this->belongsTo(Formule::class);
    }

    /** @return HasMany<Facture, $this> */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /** @return HasOne<Contrat, $this> */
    public function contrat(): HasOne
    {
        return $this->hasOne(Contrat::class);
    }
}
