<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Facture;
use App\Models\FormationSession;
use App\Models\Inscription;
use App\Models\PieceInscription;
use App\Notifications\DossierInscriptionNotification;
use App\Notifications\InscriptionDecisionNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInscriptionController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request, FormationSession $formationSession): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Voir), 403);

        $formationSession->load(['formation', 'inscriptions.apprenant.user', 'inscriptions.facture', 'inscriptions.pieces', 'seances']);
        $seancesCount = $formationSession->seances->count();
        $peutVoirFactures = $membre->peut(Donnee::FacturesPaiements, Action::Voir);
        $peutValider = $membre->peut(Donnee::Formations, Action::Valider);
        $piecesRequises = $formationSession->formation->piecesRequises();

        return response()->json([
            'session' => [
                'ville' => $formationSession->ville,
                'formationNom' => $formationSession->formation->nom,
                'prix' => $formationSession->prix,
            ],
            'peutValider' => $peutValider,
            'piecesRequises' => $piecesRequises,
            'inscriptions' => $formationSession->inscriptions->map(fn (Inscription $inscription) => [
                'id' => $inscription->id,
                'nom' => $inscription->apprenant->user->name,
                'email' => $inscription->apprenant->user->email,
                'statut' => $inscription->statut,
                'motif' => $inscription->motif_decision,
                // Identity documents are personal data: only the members who
                // decide on the enrolment can open them.
                'pieces' => $inscription->pieces->map(fn (PieceInscription $piece) => [
                    'id' => $piece->id,
                    'libelle' => $piece->libelle,
                    'nomOriginal' => $piece->nom_original,
                    'url' => $peutValider ? route('espace-admin.pieces-inscription.show', $piece) : null,
                ])->values(),
                'dossierComplet' => $this->piecesManquantes($inscription, $piecesRequises) === [],
                'presence' => $inscription->presences()->where('present', true)->count(),
                'seancesTotal' => $seancesCount,
                'paiement' => $inscription->facture === null ? null : [
                    'statut' => $inscription->facture->statut,
                    'montant' => $inscription->facture->montant,
                    'montantPaye' => $inscription->facture->montant_paye,
                    'reference' => $inscription->facture->reference,
                    'url' => $peutVoirFactures ? route('espace-admin.factures.show', $inscription->facture) : null,
                ],
                'validable' => $inscription->statut === 'DOSSIER_EN_ETUDE'
                    || ($inscription->statut === 'EN_ATTENTE_PAIEMENT' && $this->paiementRegle($inscription)),
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
     * DAG validates the dossier of an enrolment: the registration fee is then
     * billed and the apprenant can pay it, which confirms the seat
     * (PaiementService). A free session is confirmed right away. An older
     * enrolment already paid before this step existed is confirmed as before.
     */
    public function valider(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);

        if ($inscription->statut === 'EN_ATTENTE_PAIEMENT') {
            abort_unless($this->paiementRegle($inscription), 409, "Les frais d'inscription ne sont pas encore réglés : l'inscription sera confirmée à leur paiement.");

            $inscription->update(['statut' => 'VALIDEE']);
            Notifier::envoyer($inscription->apprenant->user, new InscriptionDecisionNotification($inscription, true));
            ActivityLog::record($membre, "a validé l'inscription", $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

            return response()->json(['statut' => $inscription->statut]);
        }

        abort_unless($inscription->statut === 'DOSSIER_EN_ETUDE', 409, "Ce dossier n'est pas en attente de validation.");

        $inscription->load(['formation', 'formationSession', 'pieces']);
        $manquantes = $this->piecesManquantes($inscription, $inscription->formation->piecesRequises());

        abort_unless($manquantes === [], 409, 'Le dossier est incomplet ('.implode(', ', $manquantes).') : demandez un complément.');

        $montant = (float) $inscription->montant_negocie;

        DB::transaction(function () use ($inscription, $montant): void {
            $inscription->update([
                'statut' => $montant > 0 ? 'EN_ATTENTE_PAIEMENT' : 'VALIDEE',
                'motif_decision' => null,
                'dossier_valide_at' => now(),
            ]);

            if ($montant > 0 && $inscription->facture()->doesntExist()) {
                Facture::create([
                    'type' => 'FORMATION',
                    'client_id' => null,
                    'apprenant_id' => $inscription->apprenant_id,
                    'inscription_id' => $inscription->id,
                    'montant' => $montant,
                    'montant_paye' => 0,
                    'echeance' => $inscription->formationSession?->date_debut,
                    'statut' => 'EN_ATTENTE',
                ]);
            }
        });

        Notifier::envoyer(
            $inscription->apprenant->user,
            $montant > 0 ? new DossierInscriptionNotification($inscription, true) : new InscriptionDecisionNotification($inscription, true),
        );

        ActivityLog::record($membre, "a validé le dossier d'inscription", $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

        return response()->json(['statut' => $inscription->statut]);
    }

    /**
     * Sends the dossier back to the apprenant with what to fix; the seat
     * stays reserved meanwhile.
     */
    public function demanderComplement(Request $request, Inscription $inscription): JsonResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless($inscription->statut === 'DOSSIER_EN_ETUDE', 409, "Ce dossier n'est pas en cours d'examen.");

        $data = $request->validate([
            'motif' => ['required', 'string', 'max:1000'],
        ], [
            'motif.required' => "Indiquez à l'apprenant ce qu'il doit corriger ou ajouter.",
        ]);

        $inscription->update(['statut' => 'DOSSIER_A_COMPLETER', 'motif_decision' => $data['motif']]);

        Notifier::envoyer($inscription->apprenant->user, new DossierInscriptionNotification($inscription, false));

        ActivityLog::record($membre, 'a demandé un complément de dossier', $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

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
        abort_unless(in_array($inscription->statut, ['DOSSIER_EN_ETUDE', 'DOSSIER_A_COMPLETER', 'EN_ATTENTE_PAIEMENT'], true), 409);

        $data = $request->validate([
            'motif' => ['nullable', 'string', 'max:1000'],
        ]);

        $facture = $inscription->facture;

        abort_if(
            $facture !== null && (float) $facture->montant_paye > 0,
            409,
            'Un paiement a déjà été reçu pour cette inscription : remboursez-le avant de la refuser.'
        );

        $inscription->update(['statut' => 'ABANDONNEE', 'motif_decision' => $data['motif'] ?? null]);
        $facture?->update(['statut' => 'EN_ECHEC']);

        Notifier::envoyer($inscription->apprenant->user, new InscriptionDecisionNotification($inscription, false));

        ActivityLog::record($membre, "a refusé l'inscription", $inscription, "{$inscription->apprenant->user->name} — {$inscription->formation->nom}.");

        return response()->json(['statut' => $inscription->statut]);
    }

    /**
     * Opens a piece of an enrolment dossier, for the members who decide on it.
     */
    public function piece(Request $request, PieceInscription $pieceInscription): StreamedResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Formations, Action::Valider), 403);
        abort_unless(Storage::disk('local')->exists($pieceInscription->chemin), 404);

        $pieceInscription->load('inscription.apprenant.user');
        $extension = pathinfo($pieceInscription->chemin, PATHINFO_EXTENSION);
        $nom = str($pieceInscription->libelle.' '.$pieceInscription->inscription->apprenant->user->name)->slug().'.'.$extension;

        return Storage::disk('local')->download($pieceInscription->chemin, $nom);
    }

    /**
     * @param  list<string>  $piecesRequises
     * @return list<string>
     */
    private function piecesManquantes(Inscription $inscription, array $piecesRequises): array
    {
        $deposees = $inscription->pieces->pluck('libelle')->all();

        return array_values(array_filter($piecesRequises, fn (string $libelle) => ! in_array($libelle, $deposees, true)));
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
