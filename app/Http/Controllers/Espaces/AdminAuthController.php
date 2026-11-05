<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Login for DAG staff. It authenticates against its own `admin` guard and
 * `membres_equipe` table, entirely apart from the public login used by
 * clients and apprenants.
 */
class AdminAuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('espace-admin.dashboard');
        }

        return Inertia::render('auth/admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // One key per email+IP against guessing a password, one per IP alone
        // against trying many staff emails from the same machine.
        $cle = 'admin-login:'.Str::lower($credentials['email']).'|'.$request->ip();
        $cleIp = 'admin-login-ip:'.$request->ip();

        foreach ([$cle => 5, $cleIp => 20] as $compteur => $maximum) {
            if (RateLimiter::tooManyAttempts($compteur, $maximum)) {
                throw ValidationException::withMessages([
                    'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($compteur).' secondes.',
                ]);
            }
        }

        $connecte = Auth::guard('admin')->attempt(
            [...$credentials, 'statut' => 'ACTIF'],
            $request->boolean('remember'),
        );

        if (! $connecte) {
            RateLimiter::hit($cle);
            RateLimiter::hit($cleIp, 600);

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects ou compte désactivé.',
            ]);
        }

        RateLimiter::clear($cle);
        $request->session()->regenerate();

        ActivityLog::record($request->user('admin'), 's\'est connecté', null, 'Connexion à l\'espace administration.');

        return redirect()->intended(route('espace-admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.connexion');
    }
}
