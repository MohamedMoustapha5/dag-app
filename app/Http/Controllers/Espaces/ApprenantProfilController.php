<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApprenantProfilController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user('web');
        $apprenant = $user->apprenant;

        return Inertia::render('espaces/apprenant/profil', [
            'apprenant' => $apprenant->espaceHeaderProps(),
            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
                'telephone' => $apprenant->telephone,
                'ville' => $apprenant->ville,
                'dateNaissance' => $this->formatDate($apprenant->date_naissance),
            ],
            'notifications' => [
                'seances' => $user->notif_seances,
                'candidatures' => $user->notif_candidatures,
                'newsletter' => $user->notif_newsletter,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        $apprenant = $user->apprenant;

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'ville' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $apprenant->update([
            'telephone' => $data['telephone'] ?? null,
            'ville' => $data['ville'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
        ]);

        $user->fill(['name' => $data['nom'], 'email' => $data['email']]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Informations mises à jour.']);

        return to_route('espace-apprenant.profil');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notif_seances' => ['boolean'],
            'notif_candidatures' => ['boolean'],
            'notif_newsletter' => ['boolean'],
        ]);

        $request->user('web')->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préférences de notification mises à jour.']);

        return to_route('espace-apprenant.profil');
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so this is statically `string` here.
     */
    private function formatDate(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
