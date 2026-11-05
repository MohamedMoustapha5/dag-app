<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\PasswordValidationRules;
use App\Concerns\SharesAdminEspaceProps;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A team member changes their own password. The routes carry the
 * `auth.session` middleware, so every other open session of this account
 * is signed out as soon as the password changes.
 */
class AdminMotDePasseController extends Controller
{
    use PasswordValidationRules, SharesAdminEspaceProps;

    public function edit(Request $request): Response
    {
        return Inertia::render('espaces/admin-mot-de-passe', [
            ...$this->adminEspaceProps($this->membre($request)),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);

        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:admin'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $membre->update(['password' => $data['password']]);

        ActivityLog::record($membre, 'a changé son mot de passe', null, 'Les autres sessions ouvertes ont été fermées.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mot de passe modifié. Vos autres sessions ont été déconnectées.']);

        return to_route('espace-admin.mot-de-passe');
    }
}
