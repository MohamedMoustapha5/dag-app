<?php

namespace App\Models;

use Database\Factories\EntreprisePartenaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A partner company record managed directly by the DAG team — it has no
 * user account of its own. The partner exchanges with DAG through the
 * membre de l'équipe who follows its file, not through its own espace.
 */
#[Fillable(['nom', 'secteur', 'adresse', 'rccm', 'email', 'telephone', 'statut'])]
class EntreprisePartenaire extends Model
{
    /** @use HasFactory<EntreprisePartenaireFactory> */
    use HasFactory, HasUuids;

    protected $table = 'entreprises_partenaires';

    /** @return HasMany<OffreEmploi, $this> */
    public function offresEmploi(): HasMany
    {
        return $this->hasMany(OffreEmploi::class, 'entreprise_id');
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
