<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientParametreController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $client = $user->client;

        return Inertia::render('espaces/client/parametres', [
            'client' => ['nom' => $client->nom, 'typeLabel' => $client->typeLabel()],
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
        $user = $request->user();
        $client = $user->client;

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'telephone' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $client->update(['nom' => $data['nom'], 'telephone' => $data['telephone']]);

        $user->fill(['name' => $data['nom'], 'email' => $data['email']]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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

        $request->user()->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préférences de notification mises à jour.']);

        return to_route('espace-client.parametres');
    }
}
