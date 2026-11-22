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

/**
 * @property-read bool|null $contrat_exists Present when loaded with withExists('contrat').
 */
#[Fillable([
    'reference', 'client_id', 'entreprise_id', 'service_id', 'formule_id', 'description',
    'montant_negocie', 'message_devis', 'devis_valide_le', 'devis_refuse_le', 'motif_refus_devis', 'statut', 'est_partenaire_nova_tech', 'avancement_partenaire', 'derniere_relance_at',
])]
class Demande extends Model
{
    /** @use HasFactory<DemandeFactory> */
    use GeneratesReference, HasFactory, HasUuids;

    /**
     * Linear progression of a demande, excluding REFUSEE which is a
     * terminal state reachable from any step rather than a step itself.
     * The client signs the contract sent with the devis before paying.
     *
     * @var list<string>
     */
    public const SUIVI_STEPS = [
        'BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE', 'CONTRAT_SIGNE', 'PAYEE', 'EN_COURS', 'LIVREE', 'TERMINEE',
    ];

    /** @var array<string, string> */
    public const STATUT_LABELS = [
        'BROUILLON' => 'Brouillon', 'EN_ETUDE' => "À l'étude", 'DEVIS_ENVOYE' => 'Devis envoyé',
        'CONTRAT_SIGNE' => 'Contrat signé', 'PAYEE' => 'Payé', 'EN_COURS' => 'En cours',
        'LIVREE' => 'Livrée', 'TERMINEE' => 'Terminée', 'REFUSEE' => 'Refusée',
    ];

    /**
     * Whether the demande is at least at the given step of SUIVI_STEPS
     * (always false for a refused demande).
     */
    public function aAtteint(string $etape): bool
    {
        $actuelle = array_search($this->statut, self::SUIVI_STEPS, true);

        return $actuelle !== false && $actuelle >= array_search($etape, self::SUIVI_STEPS, true);
    }

    /**
     * A demande from a private individual: no contract, the devis is a
     * proforma the client validates online before paying. Demandes from an
     * entreprise or an organisation keep the signed contract.
     */
    public function estParticulier(): bool
    {
        return $this->client_id !== null
            && $this->entreprise_id === null
            && $this->client->type === 'PARTICULIER';
    }

    /**
     * Whether the client validated the devis (proforma) online.
     */
    public function devisEstValide(): bool
    {
        return $this->devis_valide_le !== null;
    }

    /**
     * Reasons a particulier can give when declining a devis.
     *
     * @var array<string, string>
     */
    public const MOTIFS_REFUS_DEVIS = [
        'PRIX' => 'Le prix est trop élevé',
        'DELAI' => 'Le délai ne me convient pas',
        'BESOIN' => 'Mon besoin a changé',
        'AUTRE' => 'Autre raison',
    ];

    /**
     * Whether the client declined the last devis DAG sent. A new devis
     * clears it.
     */
    public function devisEstRefuse(): bool
    {
        return $this->devis_refuse_le !== null;
    }

    /**
     * Whether the client accepted the devis. Every client validates the
     * devis (proforma) online; a demande started earlier, when the contract
     * left with the devis, counts its contract as the acceptance (sent, for
     * an entreprise; signed, for a particulier).
     */
    public function devisAccepte(): bool
    {
        if ($this->devisEstValide()) {
            return true;
        }

        if ($this->estParticulier()) {
            return $this->contratEstSigne();
        }

        // A contract sent, signed or under correction at the client's request
        // was only possible once the devis was agreed.
        return $this->contrat !== null
            && ($this->contrat->statut !== 'BROUILLON' || $this->contrat->motif_rectification !== null);
    }

    /**
     * Whether the payment of the facture is open. A particulier validates
     * the devis; an entreprise validates the devis, signs the contract then
     * sent to it, and has it validated by DAG. A particulier's demande
     * started under the contract procedure keeps going once that contract
     * is validated.
     */
    public function paiementEstOuvert(): bool
    {
        if ($this->estParticulier()) {
            return $this->devisEstValide() || $this->contratEstValide();
        }

        return $this->contratEstValide();
    }

    /**
     * The steps of the status list that apply to this demande: a
     * particulier never goes through « Contrat signé ».
     *
     * @return list<string>
     */
    public function etapesSuivi(): array
    {
        return $this->estParticulier()
            ? array_values(array_diff(self::SUIVI_STEPS, ['CONTRAT_SIGNE']))
            : self::SUIVI_STEPS;
    }

    /**
     * Whether the client signed the contract of this demande.
     */
    public function contratEstSigne(): bool
    {
        return $this->contrat !== null && $this->contrat->estSigne();
    }

    /**
     * Whether DAG validated the contract the client signed, which opens the
     * payment of the facture.
     */
    public function contratEstValide(): bool
    {
        return $this->contrat !== null && $this->contrat->estValide();
    }

    public function libelleStatut(): string
    {
        return self::STATUT_LABELS[$this->statut];
    }

    protected function casts(): array
    {
        return [
            'montant_negocie' => 'decimal:2',
            'devis_valide_le' => 'datetime',
            'devis_refuse_le' => 'datetime',
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
