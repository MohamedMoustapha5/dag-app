<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientParametreController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user('web');
        $client = $user->client;

        return Inertia::render('espaces/client/parametres', [
            'client' => $client->espaceHeaderProps(),
            'profil' => [
                'nom' => $client->nom,
                'telephone' => $client->telephone,
                'email' => $user->email,
                'ville' => $client->ville,
                'statut' => $client->statut,
            ],
            'notifications' => [
                'devis' => $user->notif_devis,
                'facture' => $user->notif_facture,
                'offres' => $user->notif_offres,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        $client = $user->client;

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'telephone' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            // A stolen session alone must not be enough to move the account to
            // another inbox (and then reset its password from there).
            'current_password' => [
                Rule::requiredIf(fn () => Str::lower((string) $request->input('email')) !== Str::lower($user->email)),
                'nullable', 'string', 'current_password:web',
            ],
        ], [
            'current_password.required' => 'Saisissez votre mot de passe actuel pour changer d\'adresse e-mail.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $client->update(['nom' => $data['nom'], 'telephone' => $data['telephone'] ?? null]);

        $user->fill(['name' => $data['nom'], 'email' => Str::lower($data['email'])]);

        $emailChange = $user->isDirty('email');

        if ($emailChange) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChange) {
            $user->sendEmailVerificationNotification();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Informations mises à jour.']);

        return to_route('espace-client.parametres');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notif_devis' => ['boolean'],
            'notif_facture' => ['boolean'],
            'notif_offres' => ['boolean'],
        ]);

        $request->user('web')->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préférences de notification mises à jour.']);

        return to_route('espace-client.parametres');
    }
}
