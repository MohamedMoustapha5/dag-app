<?php

namespace App\Console\Commands;

use App\Jobs\ConstruireDocumentPdf;
use App\Models\Attestation;
use App\Models\Contrat;
use App\Models\Facture;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Builds the stored PDF of every downloadable document that has none yet
 * (documents issued before the PDFs were stored, or whose build failed), so
 * no download has to wait for dompdf.
 */
#[Signature('documents:archiver')]
#[Description('Construit et stocke le PDF des contrats, factures et attestations qui n\'en ont pas encore')]
class ArchiverDocuments extends Command
{
    public function handle(): int
    {
        $documents = [
            'contrat(s)' => Contrat::query()->whereNull('fichier_pdf'),
            'facture(s)' => Facture::query()->whereNull('fichier_pdf')->where('statut', 'PAYEE'),
            'attestation(s)' => Attestation::query()->whereNull('fichier_pdf'),
        ];

        foreach ($documents as $libelle => $requete) {
            $construits = 0;

            foreach ($requete->lazyById() as $document) {
                if (! ConstruireDocumentPdf::aUnPdf($document)) {
                    continue;
                }

                ConstruireDocumentPdf::dispatchSync($document);

                if ($document->fresh()?->fichier_pdf !== null) {
                    $construits++;
                }
            }

            $this->info("{$construits} {$libelle} archivé(s).");
        }

        return self::SUCCESS;
    }
}
