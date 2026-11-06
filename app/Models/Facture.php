<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use App\Jobs\ConstruireDocumentPdf;
use App\Support\DocumentArchive;
use Database\Factories\FactureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<FactureFactory> */
    use GeneratesReference, HasFactory, HasUuids;

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

    protected static function booted(): void
    {
        // The PDF (a receipt) is built as soon as the facture is fully paid. It
        // shows the amounts and the status: if one of them changes afterwards,
        // it is rebuilt, or dropped when the facture is no longer paid.
        static::created(function (self $facture) {
            if ($facture->statut === 'PAYEE') {
                ConstruireDocumentPdf::dispatch($facture);
            }
        });

        static::updated(function (self $facture) {
            if (! $facture->wasChanged(['montant', 'montant_paye', 'statut', 'moyen_paiement', 'echeance'])) {
                return;
            }

            if ($facture->statut === 'PAYEE') {
                ConstruireDocumentPdf::dispatch($facture, remplacer: $facture->fichier_pdf !== null);
            } elseif ($facture->fichier_pdf !== null) {
                DocumentArchive::oublier($facture);
            }
        });
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

    /**
     * Whether this facture is billed to the given web user: their client
     * account (or its linked entreprise partenaire) or their apprenant
     * profile.
     */
    public function appartientA(User $user): bool
    {
        if ($user->client !== null) {
            return $user->client->enregistrementsClientOuEntreprise(self::class)
                ->where('id', $this->id)
                ->exists();
        }

        return $user->apprenant !== null && $this->apprenant_id === $user->apprenant->id;
    }

    /** @return HasMany<Paiement, $this> */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Display label for what this facture covers: the demande's service/
     * formule for a one-off prestation, or the subscribed formule for a
     * recurring abonnement invoice.
     */
    public function libelleObjet(): string
    {
        if ($this->demande_id !== null) {
            return $this->demande->libelleObjet();
        }

        if ($this->abonnement_id !== null) {
            $formuleNom = $this->abonnement->formule_id !== null ? $this->abonnement->formule->nom : 'Formule';

            return 'Abonnement — '.$formuleNom;
        }

        if ($this->inscription_id !== null) {
            return 'Formation — '.$this->inscription->formation->nom;
        }

        return $this->reference;
    }
}
