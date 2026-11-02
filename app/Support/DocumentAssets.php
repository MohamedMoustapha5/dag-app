<?php

namespace App\Support;

/**
 * Brand images used by the generated PDFs. The director's signature and
 * the company stamp are optional files dropped in public/images/brand:
 * until they exist, the documents leave an empty signature area.
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
        $chemin = public_path("images/brand/{$fichier}");

        if (! is_file($chemin)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($chemin));
    }
}
