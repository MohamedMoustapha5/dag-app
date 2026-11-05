<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Notifications\InscriptionDecisionNotification;
use App\Support\Notifications\Notifier;
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

        $formationSession->load(['formation', 'inscriptions.apprenant.user', 'inscriptions.facture', 'seances']);
        $seancesCount = $formationSession->seances->count();
        $peutVoirFactures = $membre->peut(Donnee::FacturesPaiements, Action::Voir);

        return response()->json([
            'session' => [
                'ville' => $formationSession->ville,
                'formationNom' => $formationSession->formation->nom,
                'prix' => $formationSession->prix,
            ],
            'peutValider' => $membre->peut(Donnee::Formations, Action::Valider),
            'inscriptions' => $formationSession->inscriptions->map(fn (Inscription $inscription) => [
                'id' => $inscription->id,
                'nom' => $inscription->apprenant->user->name,
                'email' => $inscription->apprenant->user->email,
                'statut' => $inscription->statut,
                'presence' => $inscription->presences()->where('present', true)->count(),
                'seancesTotal' => $seancesCount,
                'paiement' => $inscription->facture === null ? null : [
                    'statut' => $inscription->facture->statut,
                    'montant' => $inscription->facture->montant,
                    'montantPaye' => $inscription->facture->montant_paye,
                    'reference' => $inscription->facture->reference,
                    'url' => $peutVoirFactures ? route('espace-admin.factures.show', $inscription->facture) : null,
                ],
                'validable' => $this->paiementRegle($inscription),
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
                    $this->celluleSure($inscription->apprenant->user->name),
                    $this->celluleSure($inscription->apprenant->user->email),
                    $inscription->statut,
                ]);
            }

            fclose($out);
        }, $nomFichier, ['Content-Type' => 'text/csv']);
    }

    /**
     * Names are typed by the apprenants themselves: a value starting with
     * =, +, - or @ would run as a formula when the export is opened in a
     * spreadsheet, so it is prefixed with a quote to stay plain text.
     */
    private function celluleSure(string $valeur): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valeur) === 1 ? "'".$valeur : $valeur;
    }

    /**
     * Second step of an enrolment: once the registration fee is settled
     * (confirmed in Factures & paiements), DAG validates the seat.
     */
    public function valider(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless($inscription->statut === 'EN_ATTENTE_PAIEMENT', 409);
        abort_unless($this->paiementRegle($inscription), 409, "Les frais d'inscription ne sont pas encore réglés : confirmez d'abord le paiement.");

        $inscription->update(['statut' => 'VALIDEE']);
        Notifier::envoyer($inscription->apprenant->user, new InscriptionDecisionNotification($inscription, true));

        ActivityLog::record($membre, "a validé l'inscription", $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

        return response()->json(['statut' => $inscription->statut]);
    }

    /**
     * Turns the enrolment down and cancels its unpaid fee. Money already
     * received must be refunded first, so such an enrolment can't be refused.
     */
    public function refuser(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless($inscription->statut === 'EN_ATTENTE_PAIEMENT', 409);

        $facture = $inscription->facture;

        abort_if(
            $facture !== null && (float) $facture->montant_paye > 0,
            409,
            'Un paiement a déjà été reçu pour cette inscription : remboursez-le avant de la refuser.'
        );

        $inscription->update(['statut' => 'ABANDONNEE']);
        $facture?->update(['statut' => 'EN_ECHEC']);

        Notifier::envoyer($inscription->apprenant->user, new InscriptionDecisionNotification($inscription, false));

        ActivityLog::record($membre, "a refusé l'inscription", $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

        return response()->json(['statut' => $inscription->statut]);
    }

    /**
     * Whether the registration fee is fully paid. An enrolment made before
     * fees existed has no facture and stays validatable as before.
     */
    private function paiementRegle(Inscription $inscription): bool
    {
        return $inscription->facture === null || $inscription->facture->statut === 'PAYEE';
    }
}
