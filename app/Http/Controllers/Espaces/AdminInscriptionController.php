<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\FormationSession;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInscriptionController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request, FormationSession $formationSession): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $formationSession->load(['formation', 'inscriptions.apprenant.user', 'seances']);
        $seancesCount = $formationSession->seances->count();

        return response()->json([
            'session' => [
                'ville' => $formationSession->ville,
                'formationNom' => $formationSession->formation->nom,
            ],
            'peutValider' => $membre->peut(Donnee::Formations, Action::Valider),
            'inscriptions' => $formationSession->inscriptions->map(fn (Inscription $inscription) => [
                'id' => $inscription->id,
                'nom' => $inscription->apprenant->user->name,
                'email' => $inscription->apprenant->user->email,
                'statut' => $inscription->statut,
                'presence' => $inscription->presences()->where('present', true)->count(),
                'seancesTotal' => $seancesCount,
            ]),
        ]);
    }

    public function export(Request $request, FormationSession $formationSession): StreamedResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Telecharger), 403);

        $formationSession->load(['formation', 'inscriptions.apprenant.user']);

        $nomFichier = 'inscrits-'.str($formationSession->formation->nom.'-'.$formationSession->ville)->slug().'.csv';

        return response()->streamDownload(function () use ($formationSession) {
            $out = fopen('php://output', 'w');
            abort_if($out === false, 500, 'Impossible de générer le fichier CSV.');

            fputcsv($out, ['Apprenant', 'E-mail', 'Statut']);

            foreach ($formationSession->inscriptions as $inscription) {
                fputcsv($out, [
                    $inscription->apprenant->user->name,
                    $inscription->apprenant->user->email,
                    $inscription->statut,
                ]);
            }

            fclose($out);
        }, $nomFichier, ['Content-Type' => 'text/csv']);
    }

    public function valider(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless($inscription->statut === 'EN_ATTENTE_PAIEMENT', 409);

        $inscription->update(['statut' => 'VALIDEE']);

        return response()->json(['statut' => $inscription->statut]);
    }

    public function refuser(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless($inscription->statut === 'EN_ATTENTE_PAIEMENT', 409);

        $inscription->update(['statut' => 'ABANDONNEE']);

        return response()->json(['statut' => $inscription->statut]);
    }
}
