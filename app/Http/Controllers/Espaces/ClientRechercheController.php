<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Facture;
use App\Models\Formule;
use App\Models\Service;
use App\Models\TicketSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search bar of the client espace topbar. It only ever looks through the
 * published catalogue and the signed-in client's own records (their own or
 * their entreprise's), and every result opens a page of the espace.
 */
class ClientRechercheController extends Controller
{
    private const PAR_TYPE = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $terme = trim((string) $request->query('q', ''));

        if (mb_strlen($terme) < 2) {
            return response()->json(['resultats' => []]);
        }

        $terme = mb_substr($terme, 0, 100);
        $client = $request->user('web')->client;
        $motif = '%'.addcslashes($terme, '%_\\').'%';

        $resultats = [];

        $demandes = $client->demandes()
            ->with(['service', 'formule'])
            ->where(fn (Builder $query) => $query
                ->where('reference', 'like', $motif)
                ->orWhere('description', 'like', $motif)
                ->orWhereHas('service', fn (Builder $service) => $service->where('nom', 'like', $motif))
                ->orWhereHas('formule', fn (Builder $formule) => $formule->where('nom', 'like', $motif)))
            ->latest('updated_at')
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($demandes as $demande) {
            $resultats[] = $this->resultat('Demande', $demande->libelleObjet(), $demande->reference, route('espace-client.demandes.show', $demande));
        }

        $factures = $client->enregistrementsClientOuEntreprise(Facture::class)
            ->with(['demande.service', 'demande.formule', 'abonnement.formule'])
            ->where('reference', 'like', $motif)
            ->latest()
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($factures as $facture) {
            $resultats[] = $this->resultat('Facture', $facture->reference, $facture->libelleObjet(), route('espace-client.factures'));
        }

        $contrats = $client->enregistrementsClientOuEntreprise(Contrat::class)
            ->with(['demande.service', 'demande.formule', 'abonnement.formule', 'candidature.offre', 'entreprise'])
            ->where('statut', '!=', 'BROUILLON')
            ->where('reference', 'like', $motif)
            ->latest()
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($contrats as $contrat) {
            $resultats[] = $this->resultat('Contrat', $contrat->reference, $contrat->libelleObjet(), route('espace-client.contrats.show', $contrat));
        }

        $tickets = TicketSupport::query()
            ->where('user_id', $request->user('web')->id)
            ->where(fn (Builder $query) => $query->where('reference', 'like', $motif)->orWhere('sujet', 'like', $motif))
            ->latest('dernier_message_at')
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($tickets as $ticket) {
            $resultats[] = $this->resultat('Support', $ticket->sujet, $ticket->reference, route('espace-client.support.show', $ticket));
        }

        $services = Service::publies()
            ->where(fn (Builder $query) => $query
                ->where('nom', 'like', $motif)
                ->orWhere('categorie', 'like', $motif)
                ->orWhere('description', 'like', $motif))
            ->orderBy('nom')
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($services as $service) {
            $resultats[] = $this->resultat('Service', $service->nom, $service->categorie, route('espace-client.demandes.create', ['service' => $service->id]));
        }

        $formules = Formule::query()
            ->where('statut', 'PUBLIEE')
            ->where('nom', 'like', $motif)
            ->orderBy('nom')
            ->limit(self::PAR_TYPE)
            ->get();

        foreach ($formules as $formule) {
            $resultats[] = $this->resultat('Formule', $formule->nom, $formule->prix_affiche, route('espace-client.demandes.create', ['formule' => $formule->id]));
        }

        return response()->json(['resultats' => $resultats]);
    }

    /**
     * @return array{type: string, titre: string, detail: string|null, url: string}
     */
    private function resultat(string $type, string $titre, ?string $detail, string $url): array
    {
        return [
            'type' => $type,
            'titre' => $titre,
            'detail' => $detail,
            // Relative, like the bell's links: valid whatever host serves the app.
            'url' => (string) parse_url($url, PHP_URL_PATH).(($requete = parse_url($url, PHP_URL_QUERY)) ? "?{$requete}" : ''),
        ];
    }
}
