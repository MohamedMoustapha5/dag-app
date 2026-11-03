<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CandidatureSpontanee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dossiers received by DAG from job seekers (public "candidature
 * spontanée" form), which DAG keeps and applies to offres on their behalf.
 */
class AdminCandidatExterneController extends Controller
{
    use SharesAdminEspaceProps;

    public function cv(Request $request, CandidatureSpontanee $candidatureSpontanee): StreamedResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::DossiersApprenants, Action::Telecharger), 403);

        abort_unless(Storage::disk('local')->exists($candidatureSpontanee->cv_path), 404);

        $extension = pathinfo($candidatureSpontanee->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($candidatureSpontanee->cv_path, "CV-{$candidatureSpontanee->nom}.{$extension}");
    }

    /**
     * Deletes the received dossier, its CV file and, if DAG turned it into
     * an external apprenant dossier, that dossier's account too.
     */
    public function destroy(Request $request, CandidatureSpontanee $candidatureSpontanee): RedirectResponse
    {
        abort_unless($this->membre($request)->peut(Donnee::DossiersApprenants, Action::Supprimer), 403);

        if ($candidatureSpontanee->apprenant_id !== null) {
            $candidatureSpontanee->apprenant->user->delete();
        }

        Storage::disk('local')->delete($candidatureSpontanee->cv_path);
        $candidatureSpontanee->delete();

        ActivityLog::record($this->membre($request), 'a supprimé un dossier reçu', null, "Dossier de {$candidatureSpontanee->nom}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dossier supprimé.']);

        return back();
    }
}
