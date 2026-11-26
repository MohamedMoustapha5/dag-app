<?php

namespace App\Models;

use Database\Factories\LogoPartenaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A partner logo shown on the public site. `logo` is an address from the
 * site root: an uploaded file under /storage/, or one of the brand images
 * shipped with the site.
 *
 * @property string $id
 * @property string $nom
 * @property string $logo
 * @property string|null $lien
 * @property bool $fond_sombre
 * @property bool $visible
 * @property int $ordre
 */
#[Fillable(['nom', 'logo', 'lien', 'fond_sombre', 'visible', 'ordre'])]
class LogoPartenaire extends Model
{
    /** @use HasFactory<LogoPartenaireFactory> */
    use HasFactory, HasUuids;

    public const DOSSIER = 'logos-partenaires';

    protected $table = 'logos_partenaires';

    protected function casts(): array
    {
        return [
            'fond_sombre' => 'boolean',
            'visible' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    /**
     * @param  Builder<LogoPartenaire>  $query
     * @return Builder<LogoPartenaire>
     */
    #[Scope]
    protected function ordonnes(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('created_at');
    }

    /**
     * The logos of the public site, in their order.
     *
     * @return array<int, array{nom: string, logo: string, lien: string|null, sombre: bool}>
     */
    public static function pourLeSite(): array
    {
        return self::query()
            ->where('visible', true)
            ->ordonnes()
            ->get()
            ->map(fn (self $logo) => [
                'nom' => $logo->nom,
                'logo' => $logo->logo,
                'lien' => $logo->lien,
                'sombre' => $logo->fond_sombre,
            ])
            ->all();
    }

    /**
     * Removes the uploaded file (never a brand image shipped with the site).
     */
    public function supprimerFichier(): void
    {
        if (str_starts_with($this->logo, '/storage/'.self::DOSSIER.'/')) {
            Storage::disk('public')->delete(substr($this->logo, strlen('/storage/')));
        }
    }
}
