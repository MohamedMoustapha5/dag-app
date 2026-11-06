<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Generated documents (contrats, factures, attestations) are rendered once,
 * kept on the private `local` disk under documents/, and served from there on
 * every later download instead of being rebuilt by dompdf each time. The
 * stored path lives in the document's `fichier_pdf` column.
 */
class DocumentArchive
{
    private const DISQUE = 'local';

    /**
     * @param  Closure(): string  $generer  Renders the PDF and returns its bytes.
     */
    public static function telecharger(Model $document, string $dossier, string $nomFichier, Closure $generer): Response
    {
        $chemin = self::archiver($document, $dossier, $generer);

        return response(Storage::disk(self::DISQUE)->get($chemin), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nomFichier, 'document.pdf'),
        ]);
    }

    /**
     * Returns the stored PDF of the document, generating and storing it first
     * when it does not exist yet (or its file went missing).
     *
     * @param  Closure(): string  $generer
     */
    public static function archiver(Model $document, string $dossier, Closure $generer): string
    {
        $chemin = $document->getAttribute('fichier_pdf');

        if (is_string($chemin) && $chemin !== '' && Storage::disk(self::DISQUE)->exists($chemin)) {
            return $chemin;
        }

        $chemin = "documents/{$dossier}/{$document->getKey()}.pdf";

        Storage::disk(self::DISQUE)->put($chemin, $generer());

        // Quietly: storing the file is not a change of the document itself.
        $document->forceFill(['fichier_pdf' => $chemin])->saveQuietly();

        return $chemin;
    }

    /**
     * Drops the stored PDF so the next download renders it again, for a
     * document whose content changed after it was archived.
     */
    public static function oublier(Model $document): void
    {
        $chemin = $document->getAttribute('fichier_pdf');

        if (is_string($chemin) && $chemin !== '') {
            Storage::disk(self::DISQUE)->delete($chemin);
        }

        $document->forceFill(['fichier_pdf' => null])->saveQuietly();
    }
}
