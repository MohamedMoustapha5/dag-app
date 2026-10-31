<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\ProfilPropose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientProfilProposeController extends Controller
{
    public function index(Request $request): Response
    {
        $entreprise = $request->user()->client->entreprisePartenaire;

        $profils = $entreprise->profilsProposes()
            ->with(['apprenant.user'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/profils-proposes-index', [
            'client' => $request->user()->client->espaceHeaderProps(),
            'profils' => $profils->map(fn (ProfilPropose $profil) => [
                'id' => $profil->id,
                'apprenantNom' => $profil->apprenant->user->name,
                'descriptif' => $profil->descriptif,
                'statut' => $profil->statut,
                'proposeLe' => $profil->created_at->toDateString(),
            ]),
        ]);
    }

    public function update(Request $request, ProfilPropose $profil): RedirectResponse
    {
        Gate::authorize('update', $profil);

        $data = $request->validate([
            'statut' => ['required', Rule::in(['RETENU', 'REFUSE'])],
        ]);

        $profil->update($data);

        return back();
    }
}
