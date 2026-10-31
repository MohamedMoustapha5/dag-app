<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'type', 'nom', 'secteur_activite', 'ville', 'telephone', 'statut'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasUuids;

    /** @var array<string, string> */
    private const TYPE_LABELS = [
        'PARTICULIER' => 'Particulier',
        'ENTREPRISE' => 'Entreprise',
        'ORGANISATION' => 'Organisation',
    ];

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type];
    }

    /**
     * Header/sidebar props shared by every espace-client Inertia page.
     *
     * @return array{nom: string, typeLabel: string, type: string, estPartenaireValide: bool}
     */
    public function espaceHeaderProps(): array
    {
        return [
            'nom' => $this->nom,
            'typeLabel' => $this->typeLabel(),
            'type' => $this->type,
            'estPartenaireValide' => $this->estPartenaireValide(),
        ];
    }

    /**
     * Whether DAG has validated this client as an official recruitment
     * partner — only then does its espace unlock offres/candidatures.
     */
    public function estPartenaireValide(): bool
    {
        return $this->entreprisePartenaire !== null && $this->entreprisePartenaire->statut === 'ACTIF';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<EntreprisePartenaire, $this> */
    public function entreprisePartenaire(): HasOne
    {
        return $this->hasOne(EntreprisePartenaire::class);
    }

    /** @return HasMany<Demande, $this> */
    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    /** @return HasMany<Abonnement, $this> */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    /** @return HasMany<Contrat, $this> */
    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }

    /** @return HasMany<Facture, $this> */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /**
     * Demandes/abonnements/contrats/factures each carry a `client_id` and
     * an `entreprise_id`, mutually exclusive on a single row. A validated
     * partner client can have records under either — billed as a plain
     * client before validation, and/or through the partner relationship
     * after — so this merges both sources for one model at a time.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return Builder<TModel>
     */
    public function enregistrementsClientOuEntreprise(string $model): Builder
    {
        $entrepriseId = $this->entreprisePartenaire?->id;

        return $model::query()->where(function (Builder $query) use ($entrepriseId) {
            $query->where('client_id', $this->id);

            if ($entrepriseId !== null) {
                $query->orWhere('entreprise_id', $entrepriseId);
            }
        });
    }
}
