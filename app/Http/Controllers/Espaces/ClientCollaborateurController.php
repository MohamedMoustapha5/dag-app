<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCollaborateur;
use App\Models\User;
use App\Notifications\InvitationCollaborateurNotification;
use App\Support\Notifications\Notifier;
use App\Support\SuppressionCompte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Accès & collaborateurs": the holder of an Entreprise or Organisation
 * account lets colleagues into its espace, each with their own sign-in.
 * Collaborators manage everything for the client except this page.
 */
class ClientCollaborateurController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $this->clientDuTitulaire($request);

        return Inertia::render('espaces/client/collaborateurs', [
            'client' => $client->espaceHeaderProps(),
            'titulaire' => [
                'nom' => $client->user->name,
                'email' => $client->user->email,
            ],
            'collaborateurs' => $client->collaborateurs()
                ->with('user')
                ->oldest()
                ->get()
                ->map(fn (ClientCollaborateur $collaborateur) => [
                    'id' => $collaborateur->id,
                    'nom' => $collaborateur->user->name,
                    'email' => $collaborateur->user->email,
                    'actif' => $collaborateur->estActif(),
                    'depuis' => ($collaborateur->accepte_le ?? $collaborateur->created_at)?->format('d/m/Y'),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = $this->clientDuTitulaire($request);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users', 'email')],
        ], [
            'email.unique' => 'Cette adresse e-mail a déjà un compte DAG. Invitez une autre adresse.',
        ]);

        $collaborateur = DB::transaction(function () use ($client, $data, $request): ClientCollaborateur {
            // A password nobody knows: the colleague chooses theirs from the invitation.
            $user = User::create([
                'name' => $data['nom'],
                'email' => $data['email'],
                'password' => Str::password(40),
                'type' => UserType::Client,
            ]);

            return $client->collaborateurs()->create([
                'user_id' => $user->id,
                'invite_par_id' => $request->user('web')->id,
            ]);
        });

        Notifier::envoyer($collaborateur->user, new InvitationCollaborateurNotification($collaborateur));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Invitation envoyée à {$collaborateur->user->email}.",
        ]);

        return back();
    }

    public function renvoyer(Request $request, ClientCollaborateur $collaborateur): RedirectResponse
    {
        $this->collaborateurDuClient($request, $collaborateur);
        abort_if($collaborateur->estActif(), 409, 'Cet accès est déjà activé.');

        Notifier::envoyer($collaborateur->user, new InvitationCollaborateurNotification($collaborateur));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Invitation renvoyée à {$collaborateur->user->email}.",
        ]);

        return back();
    }

    /**
     * The colleague's account only existed for this access: it is closed,
     * which signs them out everywhere. The client's records stay untouched.
     */
    public function destroy(Request $request, ClientCollaborateur $collaborateur): RedirectResponse
    {
        $this->collaborateurDuClient($request, $collaborateur);
        $nom = $collaborateur->user->name;

        SuppressionCompte::fermer($collaborateur->user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Accès retiré à {$nom}.",
        ]);

        return back();
    }

    private function clientDuTitulaire(Request $request): Client
    {
        $user = $request->user('web');
        $client = $user->client;

        abort_unless($client !== null && $client->estStructure() && $client->estTitulaire($user), 403);

        return $client;
    }

    private function collaborateurDuClient(Request $request, ClientCollaborateur $collaborateur): void
    {
        abort_unless($collaborateur->client_id === $this->clientDuTitulaire($request)->id, 404);
    }
}
