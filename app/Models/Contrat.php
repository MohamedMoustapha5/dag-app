<?php

namespace App\Models;

use App\Concerns\GeneratesReference;
use Database\Factories\ContratFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'type', 'modele_contrat_id', 'client_id', 'apprenant_id', 'entreprise_id',
    'demande_id', 'abonnement_id', 'candidature_id', 'contenu', 'montant', 'code_verification',
    'statut', 'fichier_pdf', 'photo_signature', 'signataire_nom', 'ip_signature', 'date_signature',
    'signature_contestee', 'signature_invalidee_at', 'derniere_relance_at',
])]
class Contrat extends Model
{
    /** @use HasFactory<ContratFactory> */
    use GeneratesReference, HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_signature' => 'datetime',
            'signature_contestee' => 'boolean',
            'signature_invalidee_at' => 'datetime',
            'derniere_relance_at' => 'datetime',
        ];
    }

    public static function referencePrefix(): string
    {
        return 'DAG-CTR';
    }

    protected static function booted(): void
    {
        static::creating(function (self $contrat) {
            if (empty($contrat->code_verification)) {
                $contrat->code_verification = Str::upper(Str::random(3).'-'.random_int(100, 999).Str::random(1));
            }
        });
    }

    /** @return BelongsTo<ModeleContrat, $this> */
    public function modeleContrat(): BelongsTo
    {
        return $this->belongsTo(ModeleContrat::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Apprenant, $this> */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    /** @return BelongsTo<EntreprisePartenaire, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(EntreprisePartenaire::class, 'entreprise_id');
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

    /** @return BelongsTo<Candidature, $this> */
    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class);
    }

    /** @return HasOne<Facture, $this> */
    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }

    /**
     * Display label combining the contrat's type and what it covers
     * (the formule for an abonnement, the service/formule of the demande
     * for a one-off prestation).
     */
    public function libelleObjet(): string
    {
        if ($this->type === 'ABONNEMENT') {
            $formuleNom = $this->abonnement_id !== null && $this->abonnement->formule_id !== null
                ? $this->abonnement->formule->nom
                : 'Formule';

            return 'Abonnement — '.$formuleNom;
        }

        if ($this->type === 'CONTRAT_PARTENAIRE') {
            return 'Contrat partenaire — '.($this->entreprise_id !== null ? $this->entreprise->nom : $this->reference);
        }

        if ($this->type === 'CONVENTION_STAGE') {
            $offreTitre = $this->candidature_id !== null ? $this->candidature->offre->titre : null;

            return 'Convention de stage'.($offreTitre !== null ? ' — '.$offreTitre : '');
        }

        return 'Prestation — '.($this->demande_id !== null ? $this->demande->libelleObjet() : $this->reference);
    }
}
