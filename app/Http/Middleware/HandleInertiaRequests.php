<?php

namespace App\Http\Middleware;

use App\Support\EspaceRedirect;
use App\Support\Notifications\Cloche;
use App\Support\Recaptcha;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user('web');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'cloche' => fn () => Cloche::pour($request->is('espace-admin*') ? $request->user('admin') : $user),
            'espaceUrl' => fn () => $this->espaceUrl($request),
            // The holder of an Entreprise or Organisation account sees the
            // "Accès & collaborateurs" page in its espace menu.
            'gestionAcces' => fn () => $user !== null
                && ($client = $user->client) !== null
                && $client->estStructure()
                && $client->estTitulaire($user),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            // Public key of the "Je ne suis pas un robot" widget, null while reCAPTCHA is off.
            'recaptchaSiteKey' => Recaptcha::cleDuSite(),
        ];
    }

    /**
     * Where the "Mon espace" button of the public site leads: the signed-in
     * account's own espace (client, apprenant, or the admin for a team
     * member), or nowhere when nobody is signed in.
     */
    private function espaceUrl(Request $request): ?string
    {
        $user = $request->user('web');

        if ($user !== null && $user->type !== null) {
            return EspaceRedirect::pathFor($user);
        }

        if ($request->user('admin') !== null) {
            return route('espace-admin.dashboard', absolute: false);
        }

        // Starter-kit accounts without a DAG type keep their team dashboard.
        return $user?->currentTeam !== null ? route('dashboard', ['current_team' => $user->currentTeam->slug], absolute: false) : null;
    }
}
