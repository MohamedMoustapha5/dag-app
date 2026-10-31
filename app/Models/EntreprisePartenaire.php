<?php

namespace App\Models;

use Database\Factories\EntreprisePartenaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A partner company record for recruitment/placement. Most are created
 * directly by DAG staff with no client account at all (section 6.6 of the
 * cahier des charges). A self-registered Entreprise client can also be
 * promoted into one (`client_id` set) once DAG validates it as an official
 * partner — only then does its espace unlock the recruitment features.
 */
#[Fillable(['client_id', 'nom', 'secteur', 'adresse', 'rccm', 'email', 'telephone', 'statut'])]
class EntreprisePartenaire extends Model
{
    /** @use HasFactory<EntreprisePartenaireFactory> */
    use HasFactory, HasUuids;

    protected $table = 'entreprises_partenaires';

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<OffreEmploi, $this> */
    public function offresEmploi(): HasMany
    {
        return $this->hasMany(OffreEmploi::class, 'entreprise_id');
    }

    /** @return HasMany<ProfilPropose, $this> */
    public function profilsProposes(): HasMany
    {
        return $this->hasMany(ProfilPropose::class, 'entreprise_id');
    }

    /** @return HasMany<Demande, $this> */
    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'entreprise_id');
    }

    /** @return HasMany<Abonnement, $this> */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class, 'entreprise_id');
    }

    /** @return HasMany<Contrat, $this> */
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class, 'entreprise_id');
    }

    /** @return HasMany<Facture, $this> */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'entreprise_id');
    }
}
