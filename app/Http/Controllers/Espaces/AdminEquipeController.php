<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\PasswordValidationRules;
use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\MembreEquipe;
use App\Models\User;
use App\Support\DroitsMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminEquipeController extends Controller
{
    use PasswordValidationRules, SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $this->authorizePromotrice($request);

        $membres = MembreEquipe::query()
            ->with('user')
            ->get()
            ->sortBy(fn (MembreEquipe $membre) => $membre->user->name);

        return Inertia::render('espaces/admin-equipe', [
            ...$this->adminEspaceProps($request->user()->membreEquipe),
            'membres' => $membres->map(fn (MembreEquipe $membre) => [
                'id' => $membre->id,
                'nom' => $membre->user->name,
                'email' => $membre->user->email,
                'role' => $membre->role->value,
                'roleLabel' => $membre->role->label(),
                'statut' => $membre->statut,
                'soiMeme' => $membre->user_id === $request->user()->id,
            ])->values(),
            'roles' => collect(MembreEquipeRole::cases())->map(fn (MembreEquipeRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'domaines' => collect(Donnee::cases())->map(fn (Donnee $donnee) => [
                'value' => $donnee->value,
                'label' => $donnee->label(),
            ]),
            'matriceRoles' => collect(MembreEquipeRole::cases())->map(fn (MembreEquipeRole $role) => [
                'role' => $role->value,
                'roleLabel' => $role->label(),
                'droits' => collect(DroitsMatrix::pourRole($role))->map(fn ($niveau) => $niveau->value),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePromotrice($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(MembreEquipeRole::class)],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'type' => UserType::MembreEquipe,
                'email_verified_at' => now(),
            ]);

            MembreEquipe::create([
                'user_id' => $user->id,
                'role' => $data['role'],
                'statut' => 'ACTIF',
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Compte créé pour {$data['name']}."]);

        return back();
    }

    public function update(Request $request, MembreEquipe $membreEquipe): RedirectResponse
    {
        $this->authorizePromotrice($request);

        $data = $request->validate([
            'role' => ['sometimes', Rule::enum(MembreEquipeRole::class)],
            'statut' => ['sometimes', Rule::in(['ACTIF', 'INACTIF'])],
        ]);

        abort_if(
            ($data['statut'] ?? null) === 'INACTIF' && $membreEquipe->user_id === $request->user()->id,
            403,
            'Vous ne pouvez pas désactiver votre propre compte.'
        );

        $membreEquipe->update($data);

        return back();
    }

    private function authorizePromotrice(Request $request): void
    {
        // Section 6.6/6.9 du cahier des charges : seule la Promotrice peut
        // créer ou gérer les comptes de l'équipe DAG — ce n'est pas une
        // donnée de la matrice d'accès (App\Enums\Donnee), c'est une
        // habilitation à part, réservée au rôle à accès total.
        abort_unless($request->user()->membreEquipe->role === MembreEquipeRole::Promotrice, 403);
    }
}
