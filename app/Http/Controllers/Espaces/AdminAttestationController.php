<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\Inscription;
use App\Models\Seance;
use App\Support\DocumentAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminAttestationController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::DossiersApprenants, Action::Valider), 403);

        $paires = Attestation::query()
            ->get(['apprenant_id', 'formation_id'])
            ->map(fn (Attestation $attestation) => $attestation->apprenant_id.'|'.$attestation->formation_id)
            ->flip();

        $enAttente = Inscription::query()
            ->whereIn('statut', ['VALIDEE', 'FORMEE'])
            ->with(['apprenant.user', 'formation', 'formationSession.seances'])
            ->get()
            ->filter(fn (Inscription $inscription) => $this->formationTerminee($inscription))
            ->filter(fn (Inscription $inscription) => ! $paires->has($inscription->apprenant_id.'|'.$inscription->formation_id))
            ->unique(fn (Inscription $inscription) => $inscription->apprenant_id.'|'.$inscription->formation_id)
            ->values();

        $validees = Attestation::query()
            ->with(['apprenant.user', 'formation'])
            ->latest('date_emission')
            ->get();

        return Inertia::render('espaces/admin-attestations', [
            ...$this->adminEspaceProps($membre),
            'peutTelecharger' => $membre->peut(Donnee::DossiersApprenants, Action::Telecharger),
            'enAttente' => $enAttente->map(fn (Inscription $inscription) => [
                'inscriptionId' => $inscription->id,
                'apprenantNom' => $inscription->apprenant->user->name,
                'formation' => $inscription->formation->nom,
            ]),
            'validees' => $validees->map(fn (Attestation $attestation) => [
                'id' => $attestation->id,
                'reference' => $attestation->reference,
                'apprenantNom' => $attestation->apprenant->user->name,
                'formation' => $attestation->formation->nom,
                'dateEmission' => $this->formatDate($attestation->date_emission),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::DossiersApprenants, Action::Valider), 403);

        $data = $request->validate([
            'inscription_id' => ['required', 'uuid', 'exists:inscriptions,id'],
        ]);

        $inscription = Inscription::with(['apprenant.user', 'formation'])
            ->where('id', $data['inscription_id'])
            ->firstOrFail();

        abort_if(
            Attestation::where('apprenant_id', $inscription->apprenant_id)
                ->where('formation_id', $inscription->formation_id)
                ->exists(),
            409
        );

        Attestation::create([
            'apprenant_id' => $inscription->apprenant_id,
            'formation_id' => $inscription->formation_id,
            'date_emission' => now()->toDateString(),
            'valide_par_id' => $this->membre($request)->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Attestation validée pour {$inscription->apprenant->user->name} — {$inscription->formation->nom}.",
        ]);

        return back();
    }

    public function pdf(Request $request, Attestation $attestation): HttpResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::DossiersApprenants, Action::Telecharger), 403);

        return self::genererPdf($attestation);
    }

    /**
     * Shared by the admin and the apprenant downloads: the PDF is rendered
     * on demand from the validated attestation, never stored.
     */
    public static function genererPdf(Attestation $attestation): HttpResponse
    {
        $attestation->load(['apprenant.user', 'formation']);

        return Pdf::loadView('pdf.attestation', [
            'attestation' => $attestation,
            'apprenant' => $attestation->apprenant->user->name,
            'formation' => $attestation->formation->nom,
            'duree' => $attestation->formation->duree,
            'dateEmission' => Carbon::parse($attestation->date_emission)->format('d/m/Y'),
            'signature' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ])->setPaper('a4', 'landscape')->download("attestation-{$attestation->reference}.pdf");
    }

    /**
     * A formation counts as finished once it has at least one séance and
     * every séance's date has passed.
     */
    private function formationTerminee(Inscription $inscription): bool
    {
        $seances = $inscription->formationSession->seances ?? new Collection;

        if ($seances->isEmpty()) {
            return false;
        }

        return $seances->every(fn (Seance $seance) => $seance->date < now()->toDateString());
    }

    /**
     * Normalizes a `date`-cast attribute to a Y-m-d string. Explicit
     * rather than a nullsafe `?->toDateString()` because Larastan's
     * schema-derived column type for `date` (as opposed to `datetime`)
     * loses the Carbon type, so this is statically `string` here.
     */
    private function formatDate(string $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
