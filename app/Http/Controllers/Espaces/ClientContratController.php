<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Jobs\ConstruireDocumentPdf;
use App\Models\ActivityLog;
use App\Models\Contrat;
use App\Notifications\ContratSigneNotification;
use App\Support\ContratPdf;
use App\Support\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClientContratController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $client = $request->user('web')->client;

        $contrats = $client->enregistrementsClientOuEntreprise(Contrat::class)
            ->where('statut', '!=', 'BROUILLON')
            ->with(['demande.service', 'demande.formule', 'abonnement.formule', 'candidature.offre'])
            ->latest()
            ->get();

        return Inertia::render('espaces/client/contrats-index', [
            'client' => $client->espaceHeaderProps(),
            'contrats' => $contrats->map(fn (Contrat $contrat) => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'statut' => $contrat->statut,
                'estSigne' => $contrat->estSigne(),
            ]),
        ]);
    }

    public function show(Request $request, Contrat $contrat): Response
    {
        $this->autoriser($request, $contrat);

        $contrat->load(['demande.service', 'demande.formule', 'abonnement.formule', 'candidature.offre']);
        $client = $request->user('web')->client;

        return Inertia::render('espaces/client/contrats-show', [
            'client' => $client->espaceHeaderProps(),
            'identite' => [
                'exigee' => $contrat->exigeIdentite(),
                'telephone' => $contrat->telephone_cocontractant ?? $client->telephone,
                'cniNumero' => $contrat->cni_numero,
                'cniLieuDelivrance' => $contrat->cni_lieu_delivrance,
                'cniDateDelivrance' => $contrat->cni_date_delivrance?->toDateString(),
                'villeResidence' => $contrat->ville_residence ?? $client->ville,
                'quartierPrestation' => $contrat->quartier_prestation,
            ],
            'rectification' => [
                'restantes' => $contrat->rectificationsRestantes(),
                'max' => Contrat::RECTIFICATIONS_MAX,
                'peutDemander' => $contrat->statut === 'ENVOYE' && $contrat->exigeIdentite() && $contrat->rectificationsRestantes() > 0,
            ],
            'contrat' => [
                'id' => $contrat->id,
                'reference' => $contrat->reference,
                'objet' => $contrat->libelleObjet(),
                'statut' => $contrat->statut,
                'montant' => $contrat->montant,
                'contenu' => $contrat->contenu,
                'codeVerification' => $contrat->code_verification,
                'signataireNom' => $contrat->signataire_nom,
                'dateSignature' => $contrat->date_signature?->format('d/m/Y à H:i'),
                'estSigne' => $contrat->estSigne(),
                'peutSigner' => $contrat->statut === 'ENVOYE',
                'nomSuggere' => $request->user('web')->client->nom,
            ],
        ]);
    }

    /**
     * The client signs by importing a picture of their handwritten
     * signature; the date, IP address and typed name are kept as evidence.
     */
    public function signer(Request $request, Contrat $contrat): RedirectResponse
    {
        $this->autoriser($request, $contrat);

        abort_unless($contrat->statut === 'ENVOYE', 409, "Ce contrat n'est pas ouvert à la signature.");

        // A service contract identifies the client as the DAG contract requires.
        $identite = $contrat->exigeIdentite() ? 'required' : 'nullable';

        $data = $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
            'nom' => ['required', 'string', 'max:191'],
            'accepte' => ['accepted'],
            'telephone' => [$identite, 'string', 'max:30', 'regex:/^[0-9+\s().-]{8,30}$/'],
            'cni_numero' => [$identite, 'string', 'max:50'],
            'cni_lieu_delivrance' => [$identite, 'string', 'max:120'],
            'cni_date_delivrance' => [$identite, 'date', 'before_or_equal:today'],
            'ville_residence' => [$identite, 'string', 'max:120'],
            'quartier_prestation' => [$identite, 'string', 'max:120'],
        ], [
            'signature.required' => 'Importez une image de votre signature.',
            'signature.image' => 'La signature doit être une image (PNG ou JPG).',
            'accepte.accepted' => 'Vous devez accepter les termes du contrat pour le signer.',
            'telephone.required' => 'Indiquez votre numéro de téléphone.',
            'telephone.regex' => 'Saisissez un numéro de téléphone valide.',
            'cni_numero.required' => 'Indiquez le numéro de votre CNI.',
            'cni_lieu_delivrance.required' => 'Indiquez le lieu de délivrance de votre CNI.',
            'cni_date_delivrance.required' => 'Indiquez la date de délivrance de votre CNI.',
            'cni_date_delivrance.before_or_equal' => 'La date de délivrance ne peut pas être dans le futur.',
            'ville_residence.required' => 'Indiquez votre ville de résidence.',
            'quartier_prestation.required' => 'Indiquez le quartier de la prestation.',
        ]);

        $chemin = $request->file('signature')->store('signatures-contrats');

        abort_if($chemin === false, 500, "La signature n'a pas pu être enregistrée.");

        // Conditional on ENVOYE so two simultaneous submissions can't both sign
        // and leave the evidence of the first one overwritten by the second.
        $signe = Contrat::whereKey($contrat->id)->where('statut', 'ENVOYE')->update([
            'photo_signature' => $chemin,
            'signataire_nom' => $data['nom'],
            'ip_signature' => $request->ip(),
            'date_signature' => now(),
            'statut' => 'SIGNE',
            'telephone_cocontractant' => $data['telephone'] ?? null,
            'cni_numero' => $data['cni_numero'] ?? null,
            'cni_lieu_delivrance' => $data['cni_lieu_delivrance'] ?? null,
            'cni_date_delivrance' => $data['cni_date_delivrance'] ?? null,
            'ville_residence' => $data['ville_residence'] ?? null,
            'quartier_prestation' => $data['quartier_prestation'] ?? null,
        ]);

        if ($signe === 0) {
            Storage::delete($chemin);

            abort(409, "Ce contrat n'est pas ouvert à la signature.");
        }

        $contrat->refresh();

        // Signing opens the payment of the demande's facture.
        $demande = $contrat->demande;

        if ($demande !== null && ! $demande->aAtteint('CONTRAT_SIGNE') && $demande->statut !== 'REFUSEE') {
            $demande->update(['statut' => 'CONTRAT_SIGNE']);
        }

        // The signed PDF is built now (the conditional update above bypasses the
        // model events), so the document kept as evidence is the one produced at
        // the moment of signature.
        ConstruireDocumentPdf::dispatch($contrat);

        Notifier::envoyer($request->user('web'), new ContratSigneNotification($contrat));
        Notifier::alerterEquipe(
            Donnee::ContratsClauses,
            Action::Modifier,
            'contrat.signe',
            "Contrat signé — {$contrat->reference}",
            $demande !== null
                ? "{$data['nom']} a signé le contrat « {$contrat->libelleObjet()} ». Le paiement de la facture lui est maintenant ouvert."
                : "{$data['nom']} a signé le contrat « {$contrat->libelleObjet()} ».",
            route('espace-admin.contrats.show', $contrat),
        );

        ActivityLog::record(null, 'contrat signé par le client', $contrat, "{$contrat->reference} signé par {$data['nom']}.");

        // Next step of the request: back to the demande, where the payment is now open.
        if ($demande !== null && $request->user('web')->can('view', $demande)) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat signé. Vous pouvez maintenant régler votre facture.']);

            return to_route('espace-client.demandes.show', $demande);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat signé. Merci !']);

        return back();
    }

    /**
     * The client asks DAG to revise the contract before signing it, at most
     * Contrat::RECTIFICATIONS_MAX times; the contract goes back to the team
     * as a draft. Beyond that, a new contract has to be drawn up.
     */
    public function demanderRectification(Request $request, Contrat $contrat): RedirectResponse
    {
        $this->autoriser($request, $contrat);

        abort_unless($contrat->statut === 'ENVOYE' && $contrat->exigeIdentite(), 409, "Ce contrat ne peut pas faire l'objet d'une rectification.");
        abort_if(
            $contrat->rectificationsRestantes() === 0,
            409,
            "Vous avez utilisé vos deux demandes de rectification : contactez l'équipe DAG pour l'établissement d'un nouveau contrat."
        );

        $data = $request->validate([
            'motif' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'motif.required' => 'Précisez ce qui doit être rectifié dans le contrat.',
            'motif.min' => 'Précisez ce qui doit être rectifié dans le contrat.',
        ]);

        // Conditional on ENVOYE so the request can't race with a signature.
        $modifie = Contrat::whereKey($contrat->id)
            ->where('statut', 'ENVOYE')
            ->where('nombre_rectifications', '<', Contrat::RECTIFICATIONS_MAX)
            ->update([
                'statut' => 'BROUILLON',
                'nombre_rectifications' => (int) $contrat->nombre_rectifications + 1,
                'motif_rectification' => $data['motif'],
                'rectification_demandee_at' => now(),
            ]);

        abort_if($modifie === 0, 409, "Ce contrat ne peut plus faire l'objet d'une rectification.");

        $contrat->refresh();

        Notifier::alerterEquipe(
            Donnee::ContratsClauses,
            Action::Modifier,
            'contrat.rectification',
            "Rectification demandée — {$contrat->reference}",
            "Le client demande une rectification du contrat « {$contrat->libelleObjet()} » ({$contrat->nombre_rectifications}/".Contrat::RECTIFICATIONS_MAX.') : '.str($data['motif'])->limit(160),
            route('espace-admin.contrats.show', $contrat),
        );

        ActivityLog::record(null, 'rectification de contrat demandée', $contrat, "{$contrat->reference} : demande {$contrat->nombre_rectifications}/".Contrat::RECTIFICATIONS_MAX.'.');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Demande de rectification envoyée : l'équipe DAG vous renvoie le contrat corrigé."]);

        if ($contrat->demande !== null && $request->user('web')->can('view', $contrat->demande)) {
            return to_route('espace-client.demandes.show', $contrat->demande);
        }

        return to_route('espace-client.contrats');
    }

    public function pdf(Request $request, Contrat $contrat): HttpResponse
    {
        $this->autoriser($request, $contrat);

        abort_unless($contrat->estSigne(), 409, "Ce contrat n'a pas encore été signé.");

        return ContratPdf::telecharger($contrat);
    }

    /**
     * A contrat is visible to its client (or the entreprise linked to the
     * client), and only once DAG has sent it.
     */
    private function autoriser(Request $request, Contrat $contrat): void
    {
        $client = $request->user('web')->client;

        abort_unless(
            $client->enregistrementsClientOuEntreprise(Contrat::class)
                ->where('id', $contrat->id)
                ->where('statut', '!=', 'BROUILLON')
                ->exists(),
            404
        );
    }
}
