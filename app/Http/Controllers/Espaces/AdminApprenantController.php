<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Candidature;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminApprenantController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Voir), 403);

        $apprenants = Apprenant::query()
            ->with('user')
            ->withCount('inscriptions')
            ->get()
            ->sortBy(fn (Apprenant $apprenant) => $apprenant->user->name);

        return Inertia::render('espaces/admin-apprenants', [
            ...$this->adminEspaceProps($membre),
            'apprenants' => $apprenants->map(fn (Apprenant $apprenant) => [
                'id' => $apprenant->id,
                'nom' => $apprenant->user->name,
                'email' => $apprenant->user->email,
                'ville' => $apprenant->ville,
                'inscriptionsCount' => $apprenant->inscriptions_count,
                'aUnCv' => $apprenant->cv_path !== null,
            ])->values(),
        ]);
    }

    public function show(Request $request, Apprenant $apprenant): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Voir), 403);

        $apprenant->load(['user', 'inscriptions.formation', 'candidatures.offre.entreprise', 'attestations.formation']);

        return Inertia::render('espaces/admin-apprenant-show', [
            ...$this->adminEspaceProps($membre),
            'apprenant' => [
                'id' => $apprenant->id,
                'nom' => $apprenant->user->name,
                'email' => $apprenant->user->email,
                'telephone' => $apprenant->telephone,
                'ville' => $apprenant->ville,
                'cvNomFichier' => $apprenant->cv_path !== null ? basename($apprenant->cv_path) : null,
                'consentementCv' => $apprenant->consentement_cv,
            ],
            'inscriptions' => $apprenant->inscriptions->map(fn (Inscription $inscription) => [
                'id' => $inscription->id,
                'formation' => $inscription->formation->nom,
                'statut' => $inscription->statut,
            ]),
            'candidatures' => $apprenant->candidatures->map(fn (Candidature $candidature) => [
                'id' => $candidature->id,
                'offreTitre' => $candidature->offre->titre,
                'entrepriseNom' => $candidature->offre->entreprise->nom,
                'statut' => $candidature->statut,
            ]),
            'attestations' => $apprenant->attestations->map(fn (Attestation $attestation) => [
                'id' => $attestation->id,
                'reference' => $attestation->reference,
                'formation' => $attestation->formation->nom,
            ]),
        ]);
    }

    public function cv(Request $request, Apprenant $apprenant): StreamedResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Telecharger), 403);

        abort_if($apprenant->cv_path === null, 404);

        return Storage::disk('local')->download($apprenant->cv_path, 'CV-'.$apprenant->user->name.'.pdf');
    }
}
