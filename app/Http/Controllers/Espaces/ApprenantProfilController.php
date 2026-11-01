<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantProfilController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $apprenant = $user->apprenant;

        return Inertia::render('espaces/apprenant/profil', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->fill(['name' => $data['nom'], 'email' => $data['email']]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Informations mises à jour.']);

        return to_route('espace-apprenant.profil');
    }
}
