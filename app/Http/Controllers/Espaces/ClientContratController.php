<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contrat;
use App\Support\ContratPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

        return Inertia::render('espaces/client/contrats-show', [
            'client' => $request->user('web')->client->espaceHeaderProps(),
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

        $data = $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
            'nom' => ['required', 'string', 'max:191'],
            'accepte' => ['accepted'],
        ], [
            'signature.required' => 'Importez une image de votre signature.',
            'signature.image' => 'La signature doit être une image (PNG ou JPG).',
            'accepte.accepted' => 'Vous devez accepter les termes du contrat pour le signer.',
        ]);

        $chemin = $request->file('signature')->store('signatures-contrats');

        abort_if($chemin === false, 500, "La signature n'a pas pu être enregistrée.");

        $contrat->update([
            'photo_signature' => $chemin,
            'signataire_nom' => $data['nom'],
            'ip_signature' => $request->ip(),
            'date_signature' => now(),
            'statut' => 'SIGNE',
        ]);

        ActivityLog::record(null, 'contrat signé par le client', $contrat, "{$contrat->reference} signé par {$data['nom']}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat signé. Merci !']);

        return back();
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
