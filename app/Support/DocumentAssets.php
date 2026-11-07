<?php

namespace App\Support;

/**
 * Brand images used by the generated PDFs. The director's signature and
 * the company stamp are optional files dropped in resources/brand: until
 * they exist, the documents leave an empty signature area. They must never
 * sit under public/, where anyone could download them and forge documents.
 *
 * dompdf blends a transparent PNG pixel by pixel in PHP, which costs seconds
 * per image: every image a PDF embeds is therefore given to it already laid
 * on the white of the page, opaque, which renders the same and instantly.
 */
class DocumentAssets
{
    public static function signatureDirectrice(): ?string
    {
        return self::depuisFichier('signature-directrice.png');
    }

    public static function cachet(): ?string
    {
        return self::depuisFichier('cachet-dag.png');
    }

    /**
     * Data URI of an image for a PDF; a PNG is flattened on white first.
     */
    public static function dataUri(string $contenu, string $mime): string
    {
        if ($mime === 'image/png') {
            $opaque = self::aplatirSurBlanc($contenu);

            if ($opaque !== null) {
                $contenu = $opaque;
            }
        }

        return "data:{$mime};base64,".base64_encode($contenu);
    }

    private static function depuisFichier(string $fichier): ?string
    {
        $chemin = resource_path("brand/{$fichier}");

        if (! is_file($chemin)) {
            return null;
        }

        return self::dataUri((string) file_get_contents($chemin), 'image/png');
    }

    /**
     * The PNG laid on a white background, without alpha channel, or null
     * when GD cannot read it (the original is then embedded as is).
     */
    private static function aplatirSurBlanc(string $png): ?string
    {
        $image = @imagecreatefromstring($png);

        if ($image === false) {
            return null;
        }

        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $opaque = imagecreatetruecolor($largeur, $hauteur);
        imagefill($opaque, 0, 0, (int) imagecolorallocate($opaque, 255, 255, 255));
        imagealphablending($opaque, true);
        imagecopy($opaque, $image, 0, 0, 0, 0, $largeur, $hauteur);
        imagesavealpha($opaque, false);

        ob_start();
        imagepng($opaque, null, 6);

        return (string) ob_get_clean();
    }
}
