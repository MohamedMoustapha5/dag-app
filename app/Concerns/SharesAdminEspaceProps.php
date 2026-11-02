<?php

namespace App\Concerns;

use App\Models\MembreEquipe;
use App\Support\DroitsMatrix;
use Illuminate\Support\Collection;

/**
 * Every espace-admin page needs the connected membre's role and the full
 * droits matrix for their role, so AdminLayout can gate its sidebar links
 * without each controller re-deriving it.
 */
trait SharesAdminEspaceProps
{
    /**
     * @return array{membre: array{role: string, roleLabel: string}, droits: Collection<string, string>}
     */
    protected function adminEspaceProps(MembreEquipe $membre): array
    {
        return [
            'membre' => [
                'role' => $membre->role->value,
                'roleLabel' => $membre->role->label(),
            ],
            'droits' => collect(DroitsMatrix::pourRole($membre->role))
                ->map(fn ($niveau) => $niveau->value),
        ];
    }
}
