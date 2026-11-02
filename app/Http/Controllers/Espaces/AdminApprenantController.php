<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Candidature;
use App\Models\CandidatureSpontanee;
use App\Models\Inscription;
use App\Models\OffreEmploi;
use Illuminate\Http\RedirectResponse;
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
            ->with(['user', 'inscriptions.formation', 'attestations', 'candidatureSpontanee'])
            ->withCount('inscriptions')
            ->get()
            ->sortBy(fn (Apprenant $apprenant) => $apprenant->user->name);

        $peutPostuler = $membre->peut(Donnee::EntreprisesOffres, Action::Creer)
            && $membre->peut(Donnee::DossiersApprenants, Action::Creer);

        return Inertia::render('espaces/admin-apprenants', [
            ...$this->adminEspaceProps($membre),
            'peutPostuler' => $peutPostuler,
            'peutTelecharger' => $membre->peut(Donnee::DossiersApprenants, Action::Telecharger),
            'peutSupprimer' => $membre->peut(Donnee::DossiersApprenants, Action::Supprimer),
            'offresDisponibles' => $peutPostuler
                ? OffreEmploi::publiees()
                    ->with('entreprise')
                    ->latest()
                    ->get()
                    ->map(fn (OffreEmploi $offre) => [
                        'id' => $offre->id,
                        'titre' => $offre->titre,
                        'entrepriseNom' => $offre->entreprise->nom,
                    ])
                : [],
            'dossiersRecus' => CandidatureSpontanee::query()
                ->latest()
                ->get()
                ->map(fn (CandidatureSpontanee $recue) => [
                    'id' => $recue->id,
                    'nom' => $recue->nom,
                    'email' => $recue->email,
                    'telephone' => $recue->telephone,
                    'portfolio' => $recue->portfolio,
                    'message' => $recue->message,
                    'statut' => $recue->statut,
                    'recuLe' => $recue->created_at?->format('d/m/Y'),
                    'apprenantId' => $recue->apprenant_id,
                ]),
            'apprenants' => $apprenants->map(fn (Apprenant $apprenant) => [
                'id' => $apprenant->id,
                'nom' => $apprenant->user->name,
                'email' => $apprenant->user->email,
                'ville' => $apprenant->ville,
                'inscriptionsCount' => $apprenant->inscriptions_count,
                'estForme' => $apprenant->estForme(),
                'externe' => $apprenant->estExterne(),
                'formations' => $apprenant->inscriptions
                    ->whereIn('statut', ['VALIDEE', 'FORMEE'])
                    ->map(fn (Inscription $inscription) => $inscription->formation->nom)
                    ->unique()
                    ->values(),
                'attestationsCount' => $apprenant->attestations->count(),
                'aUnCv' => $apprenant->cv_path !== null,
            ])->values(),
        ]);
    }

    public function show(Request $request, Apprenant $apprenant): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Voir), 403);

        $apprenant->load(['user', 'inscriptions.formation', 'candidatures.offre.entreprise', 'attestations.formation', 'candidatureSpontanee']);

        $peutPostuler = $membre->peut(Donnee::EntreprisesOffres, Action::Creer)
            && $apprenant->consentement_cv;

        $offresDisponibles = $peutPostuler
            ? OffreEmploi::publiees()
                ->with('entreprise')
                ->whereDoesntHave('candidatures', fn ($query) => $query->where('apprenant_id', $apprenant->id))
                ->latest()
                ->get()
                ->map(fn (OffreEmploi $offre) => [
                    'id' => $offre->id,
                    'titre' => $offre->titre,
                    'entrepriseNom' => $offre->entreprise->nom,
                ])
            : [];

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
                'estForme' => $apprenant->estForme(),
                'externe' => $apprenant->candidatureSpontanee !== null ? [
                    'portfolio' => $apprenant->candidatureSpontanee->portfolio,
                    'message' => $apprenant->candidatureSpontanee->message,
                    'recuLe' => $apprenant->candidatureSpontanee->created_at?->format('d/m/Y'),
                ] : null,
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
                'deposeeParDag' => $candidature->deposee_par_id !== null,
            ]),
            'peutTelecharger' => $membre->peut(Donnee::DossiersApprenants, Action::Telecharger),
            'peutPostuler' => $peutPostuler,
            'offresDisponibles' => $offresDisponibles,
            'attestations' => $apprenant->attestations->map(fn (Attestation $attestation) => [
                'id' => $attestation->id,
                'reference' => $attestation->reference,
                'formation' => $attestation->formation->nom,
            ]),
        ]);
    }

    /**
     * Supprime le compte d'un apprenant et, en cascade, son dossier, ses
     * inscriptions, candidatures et attestations. Ses factures et contrats
     * sont conservés mais ne sont plus rattachés à un compte.
     */
    public function destroy(Request $request, Apprenant $apprenant): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::DossiersApprenants, Action::Supprimer), 403);

        $apprenant->user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compte apprenant supprimé.']);

        return back();
    }

    public function cv(Request $request, Apprenant $apprenant): StreamedResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Telecharger), 403);

        abort_if($apprenant->cv_path === null, 404);

        return Storage::disk('local')->download($apprenant->cv_path, 'CV-'.$apprenant->user->name.'.pdf');
    }
}
