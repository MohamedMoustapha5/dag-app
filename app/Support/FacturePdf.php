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

        $facture->load(['client', 'entreprise', 'apprenant.user', 'demande.service', 'demande.formule', 'abonnement.formule', 'inscription.formation', 'paiements']);

        return Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'objet' => $facture->libelleObjet(),
            'destinataire' => self::destinataire($facture),
            'datePaiement' => self::datePaiement($facture),
        ])->download("facture-{$facture->reference}.pdf");
    }

    private static function destinataire(Facture $facture): string
    {
        if ($facture->client_id !== null) {
            return $facture->client->nom;
        }

        if ($facture->entreprise_id !== null) {
            return $facture->entreprise->nom;
        }

        if ($facture->apprenant_id !== null) {
            return $facture->apprenant->user->name;
        }

        return '—';
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
