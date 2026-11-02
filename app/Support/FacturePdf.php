<?php

namespace App\Support;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Renders the receipt-style PDF of a fully paid facture. Only the client
 * and apprenant spaces expose it; the admin side does not.
 */
class FacturePdf
{
    public static function telecharger(Facture $facture): Response
    {
        abort_unless($facture->statut === 'PAYEE', 403, 'Seule une facture payée peut être téléchargée.');

        $facture->load(['client.user', 'entreprise', 'apprenant.user', 'demande.service', 'demande.formule', 'abonnement.formule', 'inscription.formation', 'paiements']);

        [$destinataire, $contact] = self::destinataire($facture);

        return Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'objet' => $facture->libelleObjet(),
            'destinataire' => $destinataire,
            'contact' => $contact,
            'moyen' => $facture->moyen_paiement ?? '—',
            'datePaiement' => self::datePaiement($facture),
            'signature' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ])->setPaper('a4')->download("facture-{$facture->reference}.pdf");
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private static function destinataire(Facture $facture): array
    {
        if ($facture->client_id !== null) {
            return [$facture->client->nom, $facture->client->user->email];
        }

        if ($facture->entreprise_id !== null) {
            return [$facture->entreprise->nom, $facture->entreprise->email];
        }

        if ($facture->apprenant_id !== null) {
            return [$facture->apprenant->user->name, $facture->apprenant->user->email];
        }

        return ['—', null];
    }

    private static function datePaiement(Facture $facture): string
    {
        $dernier = $facture->paiements
            ->whereNotNull('date_paiement')
            ->sortByDesc('date_paiement')
            ->first();

        $date = $dernier !== null && $dernier->date_paiement !== null
            ? $dernier->date_paiement
            : $facture->updated_at;

        return $date?->format('d/m/Y') ?? '—';
    }
}
