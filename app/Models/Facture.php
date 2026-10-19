<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference', 'type', 'client_id', 'entreprise_id', 'apprenant_id', 'demande_id',
    'abonnement_id', 'inscription_id', 'contrat_id', 'montant', 'montant_paye',
    'echeance', 'statut', 'moyen_paiement',
])]
class Facture extends Model
{
    use GeneratesReference, HasUuids;

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'echeance' => 'date',
        ];
    }

    public static function referencePrefix(): string
    {
        return 'DAG-F';
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

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /** @return BelongsTo<Demande, $this> */
    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class);
    }

    /** @return BelongsTo<Abonnement, $this> */
    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }

    /** @return BelongsTo<Inscription, $this> */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /** @return BelongsTo<Contrat, $this> */
    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    /** @return HasMany<Paiement, $this> */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }
}
