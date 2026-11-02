<?php

namespace App\Concerns;

use App\Models\MembreEquipe;
use App\Support\DroitsMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Every espace-admin page needs the connected membre's role and the full
 * droits matrix for their role, so AdminLayout can gate its sidebar links
 * without each controller re-deriving it.
 */
trait SharesAdminEspaceProps
{
    /**
     * The staff member signed in on the `admin` guard.
     */
    protected function membre(Request $request): MembreEquipe
    {
        $membre = $request->user('admin');

        abort_unless($membre instanceof MembreEquipe, 403);

        return $membre;
    }

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
