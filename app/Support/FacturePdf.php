<?php

namespace App\Support;

use App\Models\Facture;
use App\Models\Paiement;
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

    /**
     * Readable name of each payment method stored on factures and paiements.
     *
     * @var array<string, string>
     */
    private const MOYENS = [
        'OM' => 'Orange Money',
        'MTN_MOMO' => 'MTN Mobile Money',
        'CARTE' => 'Carte bancaire',
        'ESPECES' => 'Espèces',
        'VIREMENT' => 'Virement bancaire',
        'MANUEL' => 'Règlement enregistré par DAG',
    ];

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
        return Pdf::loadView('pdf.facture', self::donnees($facture))->setPaper('a4')->output();
    }

    /**
     * Everything the facture template shows.
     *
     * @return array<string, mixed>
     */
    public static function donnees(Facture $facture): array
    {
        $facture->load(['client.user', 'entreprise', 'apprenant.user', 'demande.service', 'demande.formule', 'abonnement.formule', 'inscription.formation', 'paiements']);

        $dernierPaiement = $facture->paiements
            ->whereNotNull('date_paiement')
            ->sortByDesc('date_paiement')
            ->first();

        return [
            'facture' => $facture,
            'objet' => $facture->libelleObjet(),
            'nature' => self::nature($facture),
            'destinataire' => self::destinataire($facture),
            'moyen' => self::moyen($facture->moyen_paiement ?? $dernierPaiement?->moyen),
            'datePaiement' => self::datePaiement($facture, $dernierPaiement),
            'referencePaiement' => $dernierPaiement?->reference_transaction ?? $facture->reference,
            'emetteur' => config('entreprise'),
            'signature' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ];
    }

    /**
     * @return array{nom: string, type: string, email: string|null}
     */
    private static function destinataire(Facture $facture): array
    {
        if ($facture->client_id !== null) {
            return ['nom' => $facture->client->nom, 'type' => $facture->client->typeLabel(), 'email' => $facture->client->user->email];
        }

        if ($facture->entreprise_id !== null) {
            return ['nom' => $facture->entreprise->nom, 'type' => 'Entreprise partenaire', 'email' => $facture->entreprise->email];
        }

        if ($facture->apprenant_id !== null) {
            return ['nom' => $facture->apprenant->user->name, 'type' => 'Apprenant', 'email' => $facture->apprenant->user->email];
        }

        return ['nom' => '—', 'type' => '', 'email' => null];
    }

    /** Second line of the description: what kind of purchase it was. */
    private static function nature(Facture $facture): string
    {
        return match (true) {
            $facture->inscription_id !== null => 'Inscription à une session de formation',
            $facture->abonnement_id !== null => 'Abonnement',
            $facture->demande_id !== null && $facture->demande->formule_id !== null => 'Souscription à une formule',
            default => 'Prestation de service',
        };
    }

    private static function moyen(?string $code): string
    {
        return $code !== null ? (self::MOYENS[$code] ?? $code) : '—';
    }

    /** "12 novembre 2026 à 00:39 (UTC+1)", Douala time. */
    private static function datePaiement(Facture $facture, ?Paiement $dernierPaiement): string
    {
        $date = $dernierPaiement?->date_paiement ?? $facture->updated_at;

        return $date !== null
            ? $date->copy()->timezone('Africa/Douala')->locale('fr')->translatedFormat('j F Y \à H:i').' (UTC+1)'
            : '—';
    }
}
