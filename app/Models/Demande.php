<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'reference', 'client_id', 'entreprise_id', 'service_id', 'formule_id', 'description',
    'montant_negocie', 'statut', 'est_partenaire_nova_tech', 'avancement_partenaire', 'derniere_relance_at',
])]
class Demande extends Model
{
    use GeneratesReference, HasUuids;

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
            'est_partenaire_nova_tech' => 'boolean',
            'derniere_relance_at' => 'datetime',
        ];
    }

    public static function referencePrefix(): string
    {
        return 'DAG-DEM';
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

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<Formule, $this> */
    public function formule(): BelongsTo
    {
        return $this->belongsTo(Formule::class);
    }

    /** @return HasOne<Contrat, $this> */
    public function contrat(): HasOne
    {
        return $this->hasOne(Contrat::class);
    }

    /** @return HasOne<Facture, $this> */
    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }
}
