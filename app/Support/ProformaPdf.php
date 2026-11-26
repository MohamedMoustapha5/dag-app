<?php

namespace App\Support;

use App\Models\Demande;
use App\Support\Paiements\PaiementService;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * The devis of a particulier's demande, laid out as a proforma invoice:
 * the client reads it, validates it online, then pays. It follows the
 * demande (amount re-sent, validation), so it is rendered on every
 * download rather than archived like a paid facture.
 */
class ProformaPdf
{
    /** Days during which the proforma price holds. */
    public const VALIDITE_JOURS = 30;

    public static function telecharger(Demande $demande): Response
    {
        abort_unless(self::disponible($demande), 404, 'Aucun devis n\'a encore été envoyé pour cette demande.');

        return Pdf::loadView('pdf.proforma', self::donnees($demande))
            ->setPaper('a4')
            ->download('proforma-'.self::numero($demande).'.pdf');
    }

    /**
     * A proforma exists once DAG sent a devis with an amount.
     */
    public static function disponible(Demande $demande): bool
    {
        return (float) $demande->montant_negocie > 0 && $demande->aAtteint('DEVIS_ENVOYE');
    }

    /** "DAG-PRO-2026-0021" for the demande DAG-DEM-2026-0021. */
    public static function numero(Demande $demande): string
    {
        return str_replace('DAG-DEM-', 'DAG-PRO-', $demande->reference);
    }

    /**
     * Everything the proforma template shows.
     *
     * @return array<string, mixed>
     */
    public static function donnees(Demande $demande): array
    {
        $demande->loadMissing(['client.user', 'entreprise', 'service', 'formule', 'facture']);

        $emission = $demande->facture->updated_at ?? $demande->updated_at ?? now();
        $moyens = collect(app(PaiementService::class)->moyens())->pluck('label')->implode(', ');

        return [
            'demande' => $demande,
            'numero' => self::numero($demande),
            'objet' => $demande->libelleObjet(),
            'nature' => $demande->formule_id !== null ? 'Souscription à une formule' : 'Prestation de service',
            'client' => self::client($demande),
            'montant' => (float) $demande->montant_negocie,
            'dateEmission' => self::date($emission),
            'validiteJusquau' => self::date($emission->copy()->addDays(self::VALIDITE_JOURS)),
            'valideLe' => $demande->devis_valide_le !== null ? self::date($demande->devis_valide_le, avecHeure: true) : null,
            'moyens' => $moyens !== '' ? $moyens : 'Orange Money, MTN Mobile Money',
            'emetteur' => config('entreprise'),
            'signature' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ];
    }

    /**
     * Who the proforma is addressed to: the client account, or the partner
     * company a demande was opened for.
     *
     * @return array{nom: string, type: string, email: string|null, telephone: string|null}
     */
    private static function client(Demande $demande): array
    {
        if ($demande->client_id !== null) {
            return [
                'nom' => $demande->client->nom,
                'type' => $demande->client->typeLabel(),
                'email' => $demande->client->user?->email,
                'telephone' => $demande->client->telephone,
            ];
        }

        $entreprise = $demande->entreprise;

        return [
            'nom' => $entreprise->nom ?? '—',
            'type' => 'Entreprise partenaire',
            'email' => $entreprise?->email,
            'telephone' => $entreprise?->telephone,
        ];
    }

    /** "21 novembre 2026" (or "… à 14:05"), Douala time. */
    private static function date(DateTimeInterface $date, bool $avecHeure = false): string
    {
        return Carbon::instance($date)
            ->timezone('Africa/Douala')
            ->settings(['locale' => 'fr'])
            ->translatedFormat($avecHeure ? 'j F Y \à H:i' : 'j F Y');
    }
}
