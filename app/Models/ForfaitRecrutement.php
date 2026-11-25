<?php

namespace App\Models;

use Database\Factories\ForfaitRecrutementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A recruitment subscription forfait (1 month, 6 months, 1 year): what an
 * entreprise or an organisation pays for its offres and candidatures
 * pages. DAG sets the prices.
 *
 * @property string $id
 * @property string $code
 * @property string $libelle
 * @property int $duree_mois
 * @property string $prix
 */
#[Fillable(['code', 'libelle', 'duree_mois', 'prix', 'ordre'])]
class ForfaitRecrutement extends Model
{
    /** @use HasFactory<ForfaitRecrutementFactory> */
    use HasFactory, HasUuids;

    protected $table = 'forfaits_recrutement';

    protected function casts(): array
    {
        return [
            'duree_mois' => 'integer',
            'prix' => 'decimal:2',
            'ordre' => 'integer',
        ];
    }
}
