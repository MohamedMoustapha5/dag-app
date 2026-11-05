<?php

namespace App\Support;

/**
 * Brand images used by the generated PDFs. The director's signature and
 * the company stamp are optional files dropped in resources/brand: until
 * they exist, the documents leave an empty signature area. They must never
 * sit under public/, where anyone could download them and forge documents.
 */
class DocumentAssets
{
    public static function signatureDirectrice(): ?string
    {
        return self::dataUri('signature-directrice.png');
    }

    public static function cachet(): ?string
    {
        return self::dataUri('cachet-dag.png');
    }

    private static function dataUri(string $fichier): ?string
    {
        $chemin = resource_path("brand/{$fichier}");

        if (! is_file($chemin)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($chemin));
    }
}
