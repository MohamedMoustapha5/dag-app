<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LogoPartenaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The partner logos of the public site: the team adds them, orders them,
 * hides them for a while or removes them; the home band and the « À
 * propos » page follow at once.
 */
class AdminLogoPartenaireController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Voir), 403);

        return Inertia::render('espaces/admin/logos-partenaires', [
            ...$this->adminEspaceProps($membre),
            'logos' => LogoPartenaire::query()->ordonnes()->get()->map(fn (LogoPartenaire $logo) => [
                'id' => $logo->id,
                'nom' => $logo->nom,
                'logo' => $logo->logo,
                'lien' => $logo->lien,
                'fondSombre' => $logo->fond_sombre,
                'visible' => $logo->visible,
            ]),
            'actions' => [
                'creer' => $membre->peut(Donnee::EntreprisesOffres, Action::Creer),
                'modifier' => $membre->peut(Donnee::EntreprisesOffres, Action::Modifier),
                'supprimer' => $membre->peut(Donnee::EntreprisesOffres, Action::Supprimer),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Creer), 403);

        $data = $request->validate([
            ...$this->regles(),
            'logo' => ['required', ...$this->reglesImage()],
        ], $this->messages());

        $logo = LogoPartenaire::create([
            'nom' => $data['nom'],
            'lien' => $data['lien'] ?? null,
            'fond_sombre' => $request->boolean('fond_sombre'),
            'visible' => true,
            'logo' => $this->enregistrerImage($request->file('logo')),
            'ordre' => (int) LogoPartenaire::max('ordre') + 1,
        ]);

        ActivityLog::record($membre, 'a ajouté le logo partenaire', $logo, "{$logo->nom}, publié sur le site.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Logo « {$logo->nom} » ajouté : il apparaît sur le site public."]);

        return back();
    }

    /**
     * Sent as POST with _method=patch, so that a new image can come along.
     */
    public function update(Request $request, LogoPartenaire $logoPartenaire): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Modifier), 403);

        $data = $request->validate([
            ...$this->regles(),
            'logo' => ['nullable', ...$this->reglesImage()],
        ], $this->messages());

        // The replaced file is removed once the new one is recorded.
        $ancienLogo = $request->hasFile('logo') ? $logoPartenaire->replicate() : null;

        $logoPartenaire->update([
            'nom' => $data['nom'],
            'lien' => $data['lien'] ?? null,
            'fond_sombre' => $request->boolean('fond_sombre'),
            ...($request->hasFile('logo') ? ['logo' => $this->enregistrerImage($request->file('logo'))] : []),
        ]);

        $ancienLogo?->supprimerFichier();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Logo « {$logoPartenaire->nom} » enregistré."]);

        return back();
    }

    /**
     * Shows or hides a logo on the site without deleting it.
     */
    public function basculer(Request $request, LogoPartenaire $logoPartenaire): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Modifier), 403);

        $logoPartenaire->update(['visible' => ! $logoPartenaire->visible]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $logoPartenaire->visible
            ? "« {$logoPartenaire->nom} » est de nouveau affiché sur le site."
            : "« {$logoPartenaire->nom} » est masqué du site."]);

        return back();
    }

    /**
     * Moves a logo one place earlier or later in the band.
     */
    public function deplacer(Request $request, LogoPartenaire $logoPartenaire): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Modifier), 403);

        $data = $request->validate([
            'direction' => ['required', Rule::in(['avant', 'apres'])],
        ]);

        DB::transaction(function () use ($logoPartenaire, $data): void {
            // Positions are renumbered 1..n first, so equal values still move one step.
            $logos = LogoPartenaire::query()->ordonnes()->lockForUpdate()->get()->values();
            $position = $logos->search(fn (LogoPartenaire $logo) => $logo->is($logoPartenaire));
            $cible = $data['direction'] === 'avant' ? $position - 1 : $position + 1;

            if ($position === false || $cible < 0 || $cible >= $logos->count()) {
                return;
            }

            $ordre = $logos->all();
            [$ordre[$position], $ordre[$cible]] = [$ordre[$cible], $ordre[$position]];

            foreach ($ordre as $index => $logo) {
                if ($logo->ordre !== $index + 1) {
                    $logo->update(['ordre' => $index + 1]);
                }
            }
        });

        return back();
    }

    public function destroy(Request $request, LogoPartenaire $logoPartenaire): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::EntreprisesOffres, Action::Supprimer), 403);

        $logoPartenaire->delete();
        $logoPartenaire->supprimerFichier();

        ActivityLog::record($membre, 'a supprimé le logo partenaire', null, "{$logoPartenaire->nom}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Logo « {$logoPartenaire->nom} » supprimé du site."]);

        return back();
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function regles(): array
    {
        return [
            'nom' => ['required', 'string', 'max:120'],
            'lien' => ['nullable', 'url:http,https', 'max:255'],
            'fond_sombre' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Raster images only: an SVG served from the site could carry a script.
     *
     * @return list<string>
     */
    private function reglesImage(): array
    {
        return ['file', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=80,min_height=40'];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'nom.required' => 'Indiquez le nom du partenaire.',
            'logo.required' => 'Importez le logo du partenaire.',
            'logo.mimes' => 'Le logo doit être une image PNG, JPG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'logo.dimensions' => 'Le logo est trop petit : 80 × 40 pixels au moins.',
            'lien.url' => 'Le lien doit être une adresse web complète (https://…).',
        ];
    }

    private function enregistrerImage(UploadedFile $fichier): string
    {
        $chemin = $fichier->store(LogoPartenaire::DOSSIER, 'public');

        abort_if($chemin === false, 500, "Le logo n'a pas pu être enregistré.");

        // Relative to the site root, like the other uploaded images; needs `php artisan storage:link`.
        return '/storage/'.$chemin;
    }
}
