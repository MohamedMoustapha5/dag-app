<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\PasswordValidationRules;
use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Enums\NiveauDroit;
use App\Http\Controllers\Controller;
use App\Models\MembreEquipe;
use App\Support\DroitsMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->orderBy('name')
            ->get();

        return Inertia::render('espaces/admin-equipe', [
            ...$this->adminEspaceProps($this->membre($request)),
            'membres' => $membres->map(fn (MembreEquipe $membre) => [
                'id' => $membre->id,
                'nom' => $membre->name,
                'email' => $membre->email,
                'role' => $membre->role->value,
                'roleLabel' => $membre->role->label(),
                'statut' => $membre->statut,
                'soiMeme' => $membre->id === $this->membre($request)->id,
            ])->values(),
            'roles' => collect(MembreEquipeRole::cases())->map(fn (MembreEquipeRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'domaines' => collect(Donnee::cases())->map(fn (Donnee $donnee) => [
                'value' => $donnee->value,
                'label' => $donnee->label(),
            ]),
            'niveaux' => collect(NiveauDroit::cases())->map(fn (NiveauDroit $niveau) => [
                'value' => $niveau->value,
                'label' => $niveau->label(),
            ]),
            'matriceRoles' => collect(MembreEquipeRole::cases())->map(fn (MembreEquipeRole $role) => [
                'role' => $role->value,
                'roleLabel' => $role->label(),
                'modifiable' => $role !== MembreEquipeRole::Promotrice,
                'droits' => collect(DroitsMatrix::pourRole($role))->map(fn ($niveau) => $niveau->value),
                'personnalises' => collect(Donnee::cases())
                    ->filter(fn (Donnee $donnee) => DroitsMatrix::estPersonnalise($role, $donnee))
                    ->map(fn (Donnee $donnee) => $donnee->value)
                    ->values(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePromotrice($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:membres_equipe,email'],
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(MembreEquipeRole::class)],
        ]);

        MembreEquipe::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'statut' => 'ACTIF',
        ]);

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
            ($data['statut'] ?? null) === 'INACTIF' && $membreEquipe->id === $this->membre($request)->id,
            403,
            'Vous ne pouvez pas désactiver votre propre compte.'
        );

        abort_if(
            isset($data['role']) && $membreEquipe->id === $this->membre($request)->id,
            403,
            'Vous ne pouvez pas changer votre propre rôle.'
        );

        $membreEquipe->update($data);

        return back();
    }

    public function updateDroit(Request $request): RedirectResponse
    {
        $this->authorizePromotrice($request);

        $data = $request->validate([
            'role' => ['required', Rule::enum(MembreEquipeRole::class), Rule::notIn([MembreEquipeRole::Promotrice->value])],
            'donnee' => ['required', Rule::enum(Donnee::class)],
            'niveau' => ['required', Rule::enum(NiveauDroit::class)],
        ]);

        DroitsMatrix::definir(
            MembreEquipeRole::from($data['role']),
            Donnee::from($data['donnee']),
            NiveauDroit::from($data['niveau']),
        );

        return back();
    }

    public function reinitialiserDroits(Request $request, string $role): RedirectResponse
    {
        $this->authorizePromotrice($request);

        $roleEnum = MembreEquipeRole::tryFrom($role);

        abort_if($roleEnum === null || $roleEnum === MembreEquipeRole::Promotrice, 404);

        DroitsMatrix::reinitialiser($roleEnum);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Droits de « {$roleEnum->label()} » réinitialisés."]);

        return back();
    }

    private function authorizePromotrice(Request $request): void
    {
        // Section 6.6/6.9 du cahier des charges : seule la Promotrice peut
        // créer ou gérer les comptes de l'équipe DAG — ce n'est pas une
        // donnée de la matrice d'accès (App\Enums\Donnee), c'est une
        // habilitation à part, réservée au rôle à accès total.
        abort_unless(
            $this->membre($request)->role === MembreEquipeRole::Promotrice,
            403,
            "Seule la Directrice peut gérer l'équipe et les droits."
        );
    }
}
