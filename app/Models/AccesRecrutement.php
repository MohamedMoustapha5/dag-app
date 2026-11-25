<?php

namespace App\Models;

use Database\Factories\AccesRecrutementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One period of access to the recruitment pages (offres, candidatures) of
 * an entreprise or an organisation: paid through a facture, or offered by
 * DAG. It counts once ACTIF, from debut to fin included.
 *
 * @property string $id
 * @property string $client_id
 * @property string|null $forfait_id
 * @property string $libelle
 * @property int $duree_mois
 * @property string $montant
 * @property string $origine
 * @property string $statut
 * @property Carbon|null $debut
 * @property Carbon|null $fin
 */
#[Fillable(['client_id', 'forfait_id', 'libelle', 'duree_mois', 'montant', 'origine', 'statut', 'debut', 'fin', 'offert_par_id'])]
class AccesRecrutement extends Model
{
    /** @use HasFactory<AccesRecrutementFactory> */
    use HasFactory, HasUuids;

    protected $table = 'acces_recrutement';

    protected function casts(): array
    {
        return [
            'duree_mois' => 'integer',
            'montant' => 'decimal:2',
            'debut' => 'date',
            'fin' => 'date',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<ForfaitRecrutement, $this> */
    public function forfait(): BelongsTo
    {
        return $this->belongsTo(ForfaitRecrutement::class, 'forfait_id');
    }

    /** @return BelongsTo<MembreEquipe, $this> */
    public function offertPar(): BelongsTo
    {
        return $this->belongsTo(MembreEquipe::class, 'offert_par_id');
    }

    /** @return HasOne<Facture, $this> */
    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class, 'acces_recrutement_id');
    }

    /**
     * Periods that give access today.
     *
     * @param  Builder<AccesRecrutement>  $query
     * @return Builder<AccesRecrutement>
     */
    #[Scope]
    protected function enCours(Builder $query): Builder
    {
        $aujourdhui = now()->toDateString();

        return $query->where('statut', 'ACTIF')
            ->whereDate('debut', '<=', $aujourdhui)
            ->whereDate('fin', '>=', $aujourdhui);
    }
}
