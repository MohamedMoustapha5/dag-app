<?php

namespace App\Support;

use App\Models\Contrat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the signed-contract PDF: parties and clauses, then a signed
 * "procès-verbal d'acceptation" page. Every page repeats the contrat
 * reference and verification code and is numbered.
 */
class ContratPdf
{
    private const TYPES = [
        'PRESTATION_SERVICE' => 'Contrat de prestation de service',
        'ABONNEMENT' => "Contrat d'abonnement",
        'CONVENTION_STAGE' => 'Convention de stage',
        'CONTRAT_PARTENAIRE' => 'Contrat partenaire',
    ];

    public static function telecharger(Contrat $contrat): Response
    {
        $contrat->load(['client', 'apprenant.user', 'entreprise', 'demande', 'abonnement.formule', 'candidature.offre']);

        $pdf = Pdf::loadView('pdf.contrat', [
            'contrat' => $contrat,
            'typeLabel' => self::TYPES[$contrat->type],
            'objet' => $contrat->libelleObjet(),
            'partie' => $contrat->nomPartie(),
            'statutLabel' => Contrat::STATUT_LABELS[$contrat->statut],
            'signe' => $contrat->estSigne(),
            'dateSignature' => $contrat->date_signature?->format('d/m/Y H:i') ?? '—',
            'dateSignatureCourte' => $contrat->date_signature?->format('d/m/Y') ?? '—',
            'sections' => self::sections($contrat->contenu),
            'signatureClient' => self::signatureClient($contrat),
            'signatureDirectrice' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ])->setPaper('a4');

        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(
            505, 812, 'Page {PAGE_NUM} sur {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica'), 8, [0.36, 0.42, 0.51]
        );

        return $pdf->download("contrat-{$contrat->reference}.pdf");
    }

    /**
     * Splits the free-text contenu into numbered sections: a short line
     * starting with "1." / "2)" opens a section, blank lines separate
     * paragraphs.
     *
     * @return list<array{titre: string|null, paragraphes: list<string>}>
     */
    public static function sections(?string $contenu): array
    {
        if ($contenu === null || trim($contenu) === '') {
            return [];
        }

        $sections = [];
        $titre = null;
        $paragraphes = [];
        $lignes = [];

        // A trailing blank line flushes the last paragraph and section.
        foreach ([...(preg_split('/\R/', trim($contenu)) ?: []), ''] as $ligne) {
            $ligne = trim($ligne);

            $ouvreSection = preg_match('/^\d+[.)]\s+\S/', $ligne) === 1 && mb_strlen($ligne) <= 90;

            if ($ligne === '' || $ouvreSection) {
                if ($lignes !== []) {
                    $paragraphes[] = implode(' ', $lignes);
                    $lignes = [];
                }
            }

            if ($ouvreSection) {
                if ($titre !== null || $paragraphes !== []) {
                    $sections[] = ['titre' => $titre, 'paragraphes' => $paragraphes];
                }

                $titre = $ligne;
                $paragraphes = [];

                continue;
            }

            if ($ligne !== '') {
                $lignes[] = $ligne;
            }
        }

        if ($titre !== null || $paragraphes !== []) {
            $sections[] = ['titre' => $titre, 'paragraphes' => $paragraphes];
        }

        return $sections;
    }

    private static function signatureClient(Contrat $contrat): ?string
    {
        $chemin = $contrat->photo_signature;

        if ($chemin === null || ! Storage::disk('local')->exists($chemin)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($chemin) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode(Storage::disk('local')->get($chemin));
    }
}
