<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Support\EspaceRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The starter kit's team dashboard. A DAG account (client, apprenant)
     * has its own espace and never lands here: any link or bookmark to this
     * page sends it straight to that espace.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');

        if ($user->type !== null) {
            return redirect(EspaceRedirect::pathFor($user));
        }

        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
