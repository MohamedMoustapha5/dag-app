<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Database\Factories\DemandeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'reference', 'client_id', 'entreprise_id', 'service_id', 'formule_id', 'description',
    'montant_negocie', 'statut', 'est_partenaire_nova_tech', 'avancement_partenaire', 'derniere_relance_at',
])]
class Demande extends Model
{
    /** @use HasFactory<DemandeFactory> */
    use GeneratesReference, HasFactory, HasUuids;

    /**
     * Linear progression of a demande, excluding REFUSEE which is a
     * terminal state reachable from any step rather than a step itself.
     *
     * @var list<string>
     */
    public const SUIVI_STEPS = [
        'BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE', 'PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE',
    ];

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

    /**
     * Display label for the service or formule this demande is about.
     * Checks the foreign key columns rather than the loaded relations,
     * since exactly one of them is set for a client-initiated demande.
     */
    public function libelleObjet(): string
    {
        if ($this->service_id !== null) {
            return $this->service->nom;
        }

        if ($this->formule_id !== null) {
            return $this->formule->nom;
        }

        return 'Demande';
    }

    /**
     * Status label override for a demande transmitted to the Nova-Tech
     * partner, whose progress is tracked via avancement_partenaire rather
     * than the regular statut. Null when the demande is handled directly
     * by DAG, so callers fall back to the regular statut label.
     */
    public function libelleStatutPartenaire(): ?string
    {
        if (! $this->est_partenaire_nova_tech || ! $this->avancement_partenaire) {
            return null;
        }

        return match ($this->avancement_partenaire) {
            'A_TRANSMETTRE' => 'À transmettre',
            'TRANSMIS' => 'Transmis',
            'DEVIS_RECU' => 'Devis reçu',
            'EN_COURS' => 'Transmis — en cours',
            'LIVRE' => 'Livré',
        };
    }
}
