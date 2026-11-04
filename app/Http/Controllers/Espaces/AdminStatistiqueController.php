<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Enums\MembreEquipeRole;
use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\ActivityLog;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\CandidatureSpontanee;
use App\Models\Contrat;
use App\Models\Demande;
use App\Models\EntreprisePartenaire;
use App\Models\Facture;
use App\Models\Formation;
use App\Models\FormationSession;
use App\Models\MembreEquipe;
use App\Models\NotificationLog;
use App\Models\OffreEmploi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Statistiques" page of the admin space: key indicators, the activity
 * journal (Directrice only) and the central index of downloadable documents.
 * Each tab only receives what the viewer is allowed to see.
 */
class AdminStatistiqueController extends Controller
{
    use SharesAdminEspaceProps;

    private const PAR_PAGE_JOURNAL = 20;

    private const LIMITE_DOCUMENTS = 100;

    public function __invoke(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Statistiques, Action::Voir), 403);

        $peutVoir = fn (Donnee $donnee): bool => $membre->peut($donnee, Action::Voir);

        $typesDocuments = $this->typesDocumentsAutorises($membre);
        $peutJournal = $membre->role === MembreEquipeRole::Promotrice;

        $onglets = ['indicateurs'];

        if ($peutJournal) {
            $onglets[] = 'journal';
            $onglets[] = 'notifications';
        }

        if ($typesDocuments !== []) {
            $onglets[] = 'documents';
        }

        $onglet = in_array($request->query('onglet'), $onglets, true) ? $request->query('onglet') : 'indicateurs';

        // Statistics are only computed for the domains the viewer may see;
        // the rest stays null so the page shows nothing about it.
        return Inertia::render('espaces/admin-statistiques', [
            ...$this->adminEspaceProps($membre),
            'onglet' => $onglet,
            'onglets' => $onglets,
            'activite' => [
                'apprenants' => $peutVoir(Donnee::DossiersApprenants) ? Apprenant::query()->count() : null,
                'formationsPubliees' => $peutVoir(Donnee::Formations) ? Formation::query()->where('statut', 'PUBLIEE')->count() : null,
                'sessionsOuvertes' => $peutVoir(Donnee::Formations) ? FormationSession::query()->where('statut', 'OUVERTE')->count() : null,
                'entreprisesActives' => $peutVoir(Donnee::EntreprisesOffres) ? EntreprisePartenaire::query()->where('statut', 'ACTIF')->count() : null,
                'offresPubliees' => $peutVoir(Donnee::EntreprisesOffres) ? OffreEmploi::query()->where('statut', 'PUBLIEE')->count() : null,
                'abonnementsActifs' => $peutVoir(Donnee::Abonnements) ? Abonnement::query()->where('statut', 'ACTIF')->count() : null,
                'contratsActifs' => $peutVoir(Donnee::ContratsClauses) ? Contrat::query()->where('statut', 'ACTIF')->count() : null,
            ],
            'demandesParStatut' => $peutVoir(Donnee::DemandesClients)
                ? Demande::query()
                    ->selectRaw('statut, count(*) as total')
                    ->groupBy('statut')
                    ->pluck('total', 'statut')
                : null,
            'finances' => $peutVoir(Donnee::FacturesPaiements) ? [
                'factureEnAttente' => (float) Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->sum('montant'),
                'factureEncaissee' => (float) Facture::query()->sum('montant_paye'),
                'facturesImpayeesCount' => Facture::query()->whereIn('statut', ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'])->count(),
            ] : null,
            'journal' => $onglet === 'journal' ? $this->journal($request) : null,
            'notifications' => $onglet === 'notifications' ? $this->notifications($request) : null,
            'documents' => $onglet === 'documents' ? $this->documents($request, $typesDocuments) : null,
        ]);
    }

    /**
     * The activity journal: who did what, newest first, filterable by
     * author and free text. Reserved to the Directrice, since entries
     * span every domain.
     *
     * @return array<string, mixed>
     */
    private function journal(Request $request): array
    {
        $recherche = trim((string) $request->query('q', ''));
        $auteur = (string) $request->query('auteur', '');

        $entrees = ActivityLog::query()
            ->with('membreEquipe')
            ->when($recherche !== '', fn ($query) => $query->where(
                fn ($sous) => $sous->where('action', 'like', "%{$recherche}%")->orWhere('description', 'like', "%{$recherche}%")
            ))
            ->when($auteur === 'systeme', fn ($query) => $query->whereNull('membre_equipe_id'))
            ->when($auteur !== '' && $auteur !== 'systeme', fn ($query) => $query->where('membre_equipe_id', $auteur))
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PAR_PAGE_JOURNAL)
            ->withQueryString();

        return [
            'entrees' => $entrees->getCollection()->map(fn (ActivityLog $entree) => [
                'id' => $entree->id,
                'auteur' => $entree->membreEquipe?->name,
                'role' => $entree->membreEquipe?->role->label(),
                'action' => $entree->action,
                'description' => $entree->description,
                'date' => $entree->created_at?->format('d/m/Y H:i'),
            ])->values(),
            'page' => $entrees->currentPage(),
            'dernierePage' => $entrees->lastPage(),
            'total' => $entrees->total(),
            'precedente' => $entrees->previousPageUrl(),
            'suivante' => $entrees->nextPageUrl(),
            'membres' => MembreEquipe::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (MembreEquipe $membre) => ['id' => $membre->id, 'nom' => $membre->name]),
            'filtres' => ['q' => $recherche, 'auteur' => $auteur],
        ];
    }

    /**
     * Every notification DAG sent (or failed to send), whatever the channel.
     * Reserved to the Directrice, like the journal.
     *
     * @return array<string, mixed>
     */
    private function notifications(Request $request): array
    {
        $recherche = trim((string) $request->query('q', ''));
        $statut = (string) $request->query('statut', '');

        $entrees = NotificationLog::query()
            ->when($recherche !== '', fn ($query) => $query->where(
                fn ($sous) => $sous->where('titre', 'like', "%{$recherche}%")
                    ->orWhere('destinataire', 'like', "%{$recherche}%")
                    ->orWhere('evenement', 'like', "%{$recherche}%")
            ))
            ->when(in_array($statut, ['ENVOYE', 'ECHEC'], true), fn ($query) => $query->where('statut', $statut))
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PAR_PAGE_JOURNAL)
            ->withQueryString();

        return [
            'entrees' => $entrees->getCollection()->map(fn (NotificationLog $entree) => [
                'id' => $entree->id,
                'destinataire' => $entree->destinataire,
                'canal' => $entree->canal,
                'evenement' => $entree->evenement,
                'titre' => $entree->titre,
                'statut' => $entree->statut,
                'date' => $entree->created_at?->format('d/m/Y H:i'),
            ])->values(),
            'page' => $entrees->currentPage(),
            'dernierePage' => $entrees->lastPage(),
            'total' => $entrees->total(),
            'precedente' => $entrees->previousPageUrl(),
            'suivante' => $entrees->nextPageUrl(),
            'canauxActifs' => [
                'email' => (bool) config('notifications.canaux.email'),
                'sms' => (bool) config('notifications.canaux.sms'),
                'whatsapp' => (bool) config('notifications.canaux.whatsapp'),
            ],
            'filtres' => ['q' => $recherche, 'statut' => $statut],
        ];
    }

    /**
     * Document families the member may download, from the rights matrix.
     * Factures are deliberately absent: their PDF is a client and
     * apprenant document.
     *
     * @return list<string>
     */
    private function typesDocumentsAutorises(MembreEquipe $membre): array
    {
        $types = [];

        if ($membre->peut(Donnee::ContratsClauses, Action::Telecharger)) {
            $types[] = 'contrats';
        }

        if ($membre->peut(Donnee::DossiersApprenants, Action::Telecharger)) {
            $types[] = 'attestations';
            $types[] = 'cv';
        }

        return $types;
    }

    /**
     * @param  list<string>  $types
     * @return array<string, mixed>
     */
    private function documents(Request $request, array $types): array
    {
        $recherche = mb_strtolower(trim((string) $request->query('q', '')));
        $filtre = (string) $request->query('type', '');

        $tous = [
            ...(in_array('contrats', $types, true) ? $this->documentsContrats() : []),
            ...(in_array('attestations', $types, true) ? $this->documentsAttestations() : []),
            ...(in_array('cv', $types, true) ? $this->documentsCv() : []),
        ];

        $comptes = array_count_values(array_column($tous, 'type'));

        $liste = array_values(array_filter($tous, function (array $document) use ($filtre, $types, $recherche): bool {
            if ($filtre !== '' && in_array($filtre, $types, true) && $document['type'] !== $filtre) {
                return false;
            }

            return $recherche === ''
                || str_contains(mb_strtolower($document['titre'].' '.$document['partie'].' '.($document['reference'] ?? '')), $recherche);
        }));

        usort($liste, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        return [
            'liste' => array_slice($liste, 0, self::LIMITE_DOCUMENTS),
            'total' => count($liste),
            'limite' => self::LIMITE_DOCUMENTS,
            'types' => array_map(fn (string $type): array => ['value' => $type, 'total' => $comptes[$type] ?? 0], $types),
            'filtres' => ['q' => $recherche, 'type' => $filtre],
        ];
    }

    /**
     * @return list<array{type: string, titre: string, partie: string, reference: string|null, date: string, url: string}>
     */
    private function documentsContrats(): array
    {
        $documents = [];

        $contrats = Contrat::query()
            ->with(['client', 'apprenant.user', 'entreprise', 'demande', 'abonnement.formule', 'candidature.offre'])
            ->get();

        foreach ($contrats as $contrat) {
            if (! $contrat->estSigne()) {
                continue;
            }

            $documents[] = [
                'type' => 'contrats',
                'titre' => $contrat->libelleObjet(),
                'partie' => $contrat->nomPartie(),
                'reference' => $contrat->reference,
                'date' => ($contrat->date_signature ?? $contrat->created_at)?->toDateString() ?? '',
                'url' => route('espace-admin.contrats.pdf', $contrat),
            ];
        }

        return $documents;
    }

    /**
     * @return list<array{type: string, titre: string, partie: string, reference: string|null, date: string, url: string}>
     */
    private function documentsAttestations(): array
    {
        $documents = [];

        foreach (Attestation::query()->with(['apprenant.user', 'formation'])->get() as $attestation) {
            $documents[] = [
                'type' => 'attestations',
                'titre' => 'Attestation — '.$attestation->formation->nom,
                'partie' => $attestation->apprenant->user->name,
                'reference' => $attestation->reference,
                'date' => Carbon::parse($attestation->date_emission)->toDateString(),
                'url' => route('espace-admin.attestations.pdf', $attestation),
            ];
        }

        return $documents;
    }

    /**
     * CVs of apprenants and of the dossiers received from external
     * candidates (those already turned into an apprenant are listed once,
     * as apprenants).
     *
     * @return list<array{type: string, titre: string, partie: string, reference: string|null, date: string, url: string}>
     */
    private function documentsCv(): array
    {
        $documents = [];

        foreach (Apprenant::query()->whereNotNull('cv_path')->with('user')->get() as $apprenant) {
            $documents[] = [
                'type' => 'cv',
                'titre' => 'CV',
                'partie' => $apprenant->user->name,
                'reference' => null,
                'date' => $apprenant->updated_at?->toDateString() ?? '',
                'url' => route('espace-admin.apprenants.cv', $apprenant),
            ];
        }

        foreach (CandidatureSpontanee::query()->whereNull('apprenant_id')->get() as $recue) {
            $documents[] = [
                'type' => 'cv',
                'titre' => 'CV (dossier reçu)',
                'partie' => $recue->nom,
                'reference' => null,
                'date' => $recue->created_at?->toDateString() ?? '',
                'url' => route('espace-admin.candidats-externes.cv', $recue),
            ];
        }

        return $documents;
    }
}
