<?php

namespace App\Support;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Receipt-style PDF of a fully paid facture. Only the client and apprenant
 * spaces expose it; the admin side does not. It is rendered on the first
 * download and then served from the private storage (DocumentArchive).
 */
class FacturePdf
{
    public const DOSSIER = 'factures';

    public static function telecharger(Facture $facture): Response
    {
        abort_unless($facture->statut === 'PAYEE', 403, 'Seule une facture payée peut être téléchargée.');

        return DocumentArchive::telecharger(
            $facture,
            self::DOSSIER,
            "facture-{$facture->reference}.pdf",
            fn (): string => self::generer($facture),
        );
    }

    public static function generer(Facture $facture): string
    {
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
        ])->setPaper('a4')->output();
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
