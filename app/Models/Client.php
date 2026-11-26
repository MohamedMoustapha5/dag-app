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
use Illuminate\Support\Carbon;

/**
 * @property-read bool|null $partenaire_officiel Present when loaded with withExists('partenariats as partenaire_officiel').
 * @property-read string|null $fin_acces_recrutement Present when loaded with withMax('accesRecrutement as fin_acces_recrutement', 'fin').
 */
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
            'estPartenaireOfficiel' => $this->estPartenaireOfficiel(),
        ];
    }

    /**
     * Whether the recruitment pages (offres, candidatures) are open to this
     * client: an entreprise or an organisation with a recruitment
     * subscription in progress, paid or offered by DAG, whose partner record
     * DAG has not suspended. The name stays from the time DAG validated
     * partners by hand.
     */
    public function estPartenaireValide(): bool
    {
        return $this->estStructure()
            && $this->entreprisePartenaire?->statut === 'ACTIF'
            && $this->accesRecrutement()->enCours()->exists();
    }

    /**
     * The last day of recruitment access already secured (current period
     * and any period paid in advance), or null without access.
     */
    public function finAccesRecrutement(): ?Carbon
    {
        $fin = $this->accesRecrutement()
            ->where('statut', 'ACTIF')
            ->whereDate('fin', '>=', now()->toDateString())
            ->max('fin');

        return $fin !== null ? Carbon::parse($fin) : null;
    }

    /** @return HasMany<AccesRecrutement, $this> */
    public function accesRecrutement(): HasMany
    {
        return $this->hasMany(AccesRecrutement::class);
    }

    /**
     * The account holder: the person who registered the client. Only they
     * decide who else may enter the espace.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ClientCollaborateur, $this> */
    public function collaborateurs(): HasMany
    {
        return $this->hasMany(ClientCollaborateur::class);
    }

    /**
     * Entreprises and Organisations share the same espace: colleagues let in
     * (collaborateurs) and, once DAG validates them, recruitment.
     */
    public function estStructure(): bool
    {
        return in_array($this->type, ['ENTREPRISE', 'ORGANISATION'], true);
    }

    public function estTitulaire(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Whether the client is an official partner of DAG today: a partnership
     * contract signed and validated, still in force.
     */
    public function estPartenaireOfficiel(): bool
    {
        return $this->estStructure() && $this->partenariats()->enVigueur()->exists();
    }

    /** @return HasMany<Partenariat, $this> */
    public function partenariats(): HasMany
    {
        return $this->hasMany(Partenariat::class);
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
