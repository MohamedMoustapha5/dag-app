<?php

namespace App\Jobs;

use App\Models\Attestation;
use App\Models\Contrat;
use App\Models\Facture;
use App\Support\AttestationPdf;
use App\Support\ContratPdf;
use App\Support\DocumentArchive;
use App\Support\FacturePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Builds and stores the PDF of a document as soon as it exists in its final
 * form (contrat signed, facture paid, attestation validated), so downloads
 * only ever read the stored file. Dispatched after the database transaction
 * that produced the document commits, and off the request when a queue
 * worker runs.
 */
class ConstruireDocumentPdf implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contrat|Facture|Attestation $document,
        public bool $remplacer = false,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $document = $this->document->fresh();

        // Gone, or no longer in a state that has a PDF (e.g. a facture whose
        // payment was reversed): nothing to build.
        if ($document === null || ! self::aUnPdf($document)) {
            return;
        }

        if ($this->remplacer) {
            DocumentArchive::oublier($document);
        }

        // A rendering failure is reported but never breaks the payment,
        // signature or validation that triggered it (with the sync queue it
        // would surface in that request): the download builds the PDF if it
        // is still missing.
        try {
            match (true) {
                $document instanceof Contrat => ContratPdf::archiver($document),
                $document instanceof Facture => DocumentArchive::archiver($document, FacturePdf::DOSSIER, fn (): string => FacturePdf::generer($document)),
                $document instanceof Attestation => DocumentArchive::archiver($document, AttestationPdf::DOSSIER, fn (): string => AttestationPdf::generer($document)),
            };
        } catch (Throwable $erreur) {
            report($erreur);
        }
    }

    /**
     * Whether the document is in the state where it can be downloaded.
     */
    public static function aUnPdf(Contrat|Facture|Attestation $document): bool
    {
        return match (true) {
            $document instanceof Contrat => $document->estSigne(),
            $document instanceof Facture => $document->statut === 'PAYEE',
            default => true,
        };
    }
}
