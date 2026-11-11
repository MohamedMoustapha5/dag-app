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
 *
 * With --contrats, the PDF of the contracts already signed is rebuilt too,
 * with the current layout: the signed content (text, signature, date, IP,
 * verification code) is unchanged, only its layout is.
 */
#[Signature('documents:archiver {--contrats : Reconstruit aussi le PDF des contrats déjà signés avec la mise en page actuelle}')]
#[Description('Construit et stocke le PDF des contrats, factures et attestations qui n\'en ont pas encore')]
class ArchiverDocuments extends Command
{
    public function handle(): int
    {
        if ($this->option('contrats')) {
            $reconstruits = 0;

            foreach (Contrat::query()->whereNotNull('fichier_pdf')->lazyById() as $contrat) {
                if (! ConstruireDocumentPdf::aUnPdf($contrat)) {
                    continue;
                }

                ConstruireDocumentPdf::dispatchSync($contrat, true);
                $reconstruits++;
            }

            $this->info("{$reconstruits} contrat(s) signé(s) reconstruit(s) avec la mise en page actuelle.");
        }

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
