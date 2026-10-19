<?php

namespace App\Http\Responses\Concerns;

use App\Models\Team;
use App\Support\EspaceRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    /**
     * Resolve where an authenticated user lands. A DAG account (client,
     * apprenant, entreprise partenaire, membre équipe) always has a fixed
     * `type` and goes to its own espace, never a team dashboard. The
     * team-based path is kept as a fallback for accounts without a DAG
     * type, so the starter kit's own team-switching tests keep working.
     */
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $user = $request->user();

        if ($user?->type !== null) {
            return EspaceRedirect::pathFor($user);
        }

        $team = $this->currentTeam($request);

        URL::defaults(['current_team' => $team->slug]);

        return "/{$team->slug}{$redirect}";
    }

    protected function currentTeam(Request $request): Team
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $team = $user->currentTeam ?? $user->personalTeam();

        abort_if(! $team, 403);

        return $team;
    }
}
