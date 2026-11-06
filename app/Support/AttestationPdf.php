<?php

namespace App\Support;

use App\Models\Attestation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Certificate of a completed formation. Built as soon as DAG validates the
 * attestation (Attestation::booted) and stored privately; the admin and the
 * apprenant downloads both serve that stored file.
 */
class AttestationPdf
{
    public const DOSSIER = 'attestations';

    public static function telecharger(Attestation $attestation): Response
    {
        return DocumentArchive::telecharger(
            $attestation,
            self::DOSSIER,
            "attestation-{$attestation->reference}.pdf",
            fn (): string => self::generer($attestation),
        );
    }

    public static function generer(Attestation $attestation): string
    {
        $attestation->load(['apprenant.user', 'formation']);

        return Pdf::loadView('pdf.attestation', [
            'attestation' => $attestation,
            'apprenant' => $attestation->apprenant->user->name,
            'formation' => $attestation->formation->nom,
            'duree' => $attestation->formation->duree,
            'dateEmission' => Carbon::parse($attestation->date_emission)->format('d/m/Y'),
            'signature' => DocumentAssets::signatureDirectrice(),
            'cachet' => DocumentAssets::cachet(),
        ])->setPaper('a4', 'landscape')->output();
    }
}
