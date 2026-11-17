<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\ClientCollaborateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page a colleague reaches from the invitation e-mail: they choose their
 * password and enter the espace of the Entreprise or Organisation that
 * invited them. The link proves they own the address, so it is verified.
 */
class InvitationCollaborateurController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, ClientCollaborateur $collaborateur): Response
    {
        $collaborateur->load(['client', 'user', 'invitePar']);

        return Inertia::render('auth/invitation-collaborateur', [
            'entreprise' => $collaborateur->client->nom,
            'nom' => $collaborateur->user->name,
            'email' => $collaborateur->user->email,
            'invitePar' => $collaborateur->invitePar?->name,
            'dejaActive' => $collaborateur->estActif(),
            'passwordRules' => Password::default()->toPasswordRulesString(),
            // The signed address itself: the form posts back to it.
            'action' => $request->getRequestUri(),
        ]);
    }

    public function store(Request $request, ClientCollaborateur $collaborateur): RedirectResponse
    {
        if ($collaborateur->estActif()) {
            return to_route('login')->with('status', 'Votre accès est déjà activé : connectez-vous.');
        }

        $data = $request->validate(['password' => $this->passwordRules()]);

        $user = $collaborateur->user;
        $user->forceFill([
            'password' => $data['password'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $collaborateur->update(['accepte_le' => now()]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Bienvenue ! Vous avez maintenant accès à l'espace de {$collaborateur->client->nom}.",
        ]);

        return to_route('espace-client.dashboard');
    }
}
