<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat {{ $contrat->reference }}</title>
    <style>
        @page { margin: 48px 56px 70px 56px; }
        body { font-family: sans-serif; color: #071f41; font-size: 11.5px; line-height: 1.55; }
        table { border-collapse: collapse; }
        .muted { color: #5b6b82; }
        .titre { font-size: 19px; font-weight: bold; text-align: right; color: #071f41; line-height: 1.25; }
        .meta-droite { text-align: right; font-size: 10.5px; margin-top: 6px; }
        .societe { font-size: 10.5px; color: #3d4f6b; margin-top: 4px; }
        .filet { border-top: 1px solid #c9d3e3; margin: 16px 0; }
        .boite-ref { border: 1.5px solid #071f41; padding: 8px 12px; font-size: 10.5px; margin-bottom: 16px; }
        h3 { font-size: 13px; margin: 14px 0 3px; color: #071f41; }
        p { margin: 0 0 6px; }
        .base-legale { margin: 2px 0 9px 10px; padding-left: 8px; border-left: 2px solid #0166fe; font-size: 9.5px; font-style: italic; line-height: 1.5; color: #2c4a7a; }
        .base-legale strong { font-style: italic; color: #071f41; }
        .parties td { padding: 5px 8px; border-bottom: 1px solid #e3e8f0; vertical-align: top; }
        .parties td.k { width: 30%; color: #5b6b82; }
        .accord { width: 100%; }
        .accord td { border: 1px solid #dfe5f0; padding: 11px 12px; font-size: 11.5px; }
        .accord td.k { font-weight: bold; background: #f6f9ff; width: 22%; }
        .signatures td { vertical-align: top; width: 50%; padding-right: 22px; }
        .signatures .tete { border-top: 2px solid #071f41; padding-top: 7px; font-weight: bold; font-size: 12.5px; }
        .zone { height: 124px; padding-top: 8px; }
        .signataire-dag { margin-top: 2px; font-weight: bold; font-size: 12.5px; letter-spacing: 0.4px; color: #071f41; }
        .bandeau { background: #eaf1ff; border-left: 4px solid #0166fe; padding: 8px 12px; margin: 14px 0; font-size: 11px; }
        .conteste { background: #fbeaea; border-left-color: #d64545; }
        .proces-verbal { margin-top: 26px; padding-top: 14px; border-top: 1px solid #c9d3e3; page-break-inside: avoid; }
        /* Watermark, repeated behind the text of every page: the stamp,
           faded, and the name of the company across the page. */
        .filigrane-cachet { position: fixed; top: 290px; left: 141px; width: 400px; height: 400px; z-index: -1; }
        .filigrane-texte { position: fixed; top: 560px; left: -59px; width: 800px; text-align: center; font-size: 46px; font-weight: bold; letter-spacing: 5px; color: #e6eaf5; transform: rotate(-35deg); z-index: -1; }
        .pied { position: fixed; left: 0; width: 86%; bottom: -46px; border-top: 1px solid #071f41; padding-top: 5px; font-size: 8.5px; color: #3d4f6b; }
    </style>
</head>
<body>
@if($filigrane)
    <img class="filigrane-cachet" src="{{ $filigrane }}" alt="">
@endif
<div class="filigrane-texte">DIGITAL AGENCY GROUP</div>
<div class="pied">
    Réf. contrat : {{ $contrat->reference }} &nbsp;|&nbsp; Code de vérification : {{ $contrat->code_verification }}
    &nbsp;|&nbsp; Valide uniquement si toutes les pages portent la même référence et le même code.
</div>

<table width="100%">
    <tr>
        <td width="52%" style="vertical-align: top;">
            <img src="{{ public_path('images/brand/pdf/dag-logo-fond-blanc.png') }}" style="width: 175px;" alt="Digital Agency Group">
            <div class="societe">
                Digital Agency Group<br>
                Siège : Douala, Cameroun<br>
                contact@agencydag.com
            </div>
        </td>
        <td width="48%" style="vertical-align: top;">
            <div class="titre">{{ mb_strtoupper($typeLabel) }}</div>
            <div class="meta-droite">
                <strong>Référence :</strong> {{ $contrat->reference }}<br>
                <strong>Statut :</strong> {{ $statutLabel }}
            </div>
        </td>
    </tr>
</table>

<div class="filet"></div>

<div class="boite-ref">
    <strong>Réf. contrat :</strong> {{ $contrat->reference }} &nbsp;|&nbsp; <strong>Code de vérification :</strong> {{ $contrat->code_verification }}<br>
    <strong>Important :</strong> ce contrat n'est valide que si toutes les pages imprimées portent la même référence et le même code de vérification.
</div>

<h3>Parties et objet</h3>
<table class="parties" width="100%">
    @if($contrat->type === 'CONTRAT_PARTENAIRE')
        <tr><td class="k">Partenaire 1</td><td>Digital Agency Group (SARL), NIU P010618877302Q, Douala, Cameroun</td></tr>
        <tr><td class="k">Partenaire 2</td><td><strong>{{ $partie }}</strong>@if($contrat->forme_juridique !== null), {{ $contrat->forme_juridique }}@endif</td></tr>
        @if($contrat->niu !== null)
            <tr><td class="k">NIU du Partenaire 2</td><td>{{ $contrat->niu }}</td></tr>
        @endif
        @if($contrat->signataire_nom !== null)
            <tr><td class="k">Représenté par</td><td>{{ $contrat->signataire_nom }}@if($contrat->qualite_signataire !== null), {{ $contrat->qualite_signataire }}@endif</td></tr>
        @endif
    @else
    <tr><td class="k">Prestataire</td><td>Digital Agency Group, Douala, Cameroun</td></tr>
    <tr><td class="k">{{ $contrat->exigeIdentite() ? "Nom et prénom de l'intéressé" : 'Cocontractant' }}</td><td><strong>{{ $contrat->signataire_nom ?? $partie }}</strong>@if($contrat->signataire_nom !== null && $partie !== "—" && $contrat->signataire_nom !== $partie) <span class="muted">(compte : {{ $partie }})</span>@endif</td></tr>
    @endif
    @if($contrat->telephone_cocontractant !== null)
        <tr><td class="k">Téléphone</td><td>{{ $contrat->telephone_cocontractant }}</td></tr>
    @endif
    @if($contrat->cni_numero !== null)
        <tr><td class="k">CNI</td><td>N° {{ $contrat->cni_numero }} — délivrée à {{ $contrat->cni_lieu_delivrance }} le {{ $contrat->cni_date_delivrance?->format('d/m/Y') }}</td></tr>
    @endif
    @if($contrat->ville_residence !== null)
        <tr><td class="k">Ville de résidence</td><td>{{ $contrat->ville_residence }}</td></tr>
    @endif
    @if($contrat->quartier_prestation !== null)
        <tr><td class="k">Quartier de prestation</td><td>{{ $contrat->quartier_prestation }}</td></tr>
    @endif
    <tr><td class="k">Objet</td><td>{{ $objet }}</td></tr>
    @if($contrat->montant !== null)
        <tr><td class="k">Montant</td><td>{{ number_format((float) $contrat->montant, 0, ',', ' ') }} FCFA</td></tr>
    @endif
</table>

@foreach($sections as $section)
    @if($section['titre'] !== null)
        <h3>{{ $section['titre'] }}</h3>
    @endif
    @foreach($section['paragraphes'] as $paragraphe)
        @if(preg_match('/^(Base légale\s*[–-][^:]*:)\s*(.*)$/su', $paragraphe, $baseLegale))
            <p class="base-legale"><strong>{{ $baseLegale[1] }}</strong> {{ $baseLegale[2] }}</p>
        @else
            <p>{{ $paragraphe }}</p>
        @endif
    @endforeach
@endforeach

{{-- The acceptance record follows the articles directly; kept in one piece,
     it only moves to the next page when it does not fit on the current one. --}}
<div class="proces-verbal">

<h3 style="font-size: 17px; margin-top: 0;">Procès-verbal d'acceptation</h3>

<div class="bandeau {{ $contrat->signature_contestee ? 'conteste' : '' }}">
    <strong>Vérification finale :</strong> Réf. {{ $contrat->reference }} | Code {{ $contrat->code_verification }}.
    @if($contrat->signature_contestee)
        <br><strong>La signature de ce contrat a été contestée.</strong>
    @endif
</div>

<table class="accord">
    <tr>
        <td class="k">Contrat signé</td><td>{{ $signe ? 'Oui' : 'Non' }}</td>
        <td class="k">Date / heure de signature</td><td>{{ $dateSignature }}</td>
    </tr>
    <tr>
        <td class="k">Nom du signataire</td><td>{{ $contrat->signataire_nom ?? $partie }}</td>
        <td class="k">Signature contestée</td><td>{{ $contrat->signature_contestee ? 'Oui' : 'Non' }}</td>
    </tr>
    <tr>
        <td class="k">Adresse IP</td><td colspan="3">{{ $contrat->ip_signature ?? '—' }}</td>
    </tr>
</table>

<table class="signatures" width="100%" style="margin-top: 26px;">
    <tr>
        <td>
            <div class="tete">{{ $contrat->type === 'CONTRAT_PARTENAIRE' ? 'Pour ' . $partie . ' (Partenaire 2)' : 'Signature du cocontractant' }}</div>
            <div class="zone">
                @if($signatureClient)
                    <img src="{{ $signatureClient }}" style="height: 80px;" alt="">
                @endif
            </div>
            <div>{{ $contrat->signataire_nom ?? $partie }}</div>
            @if($contrat->qualite_signataire !== null)
                <div class="muted">{{ $contrat->qualite_signataire }}</div>
            @endif
            <div class="muted">Date : {{ $dateSignatureCourte }}</div>
        </td>
        <td>
            <div class="tete">Pour Digital Agency Group{{ $contrat->type === 'CONTRAT_PARTENAIRE' ? ' (Partenaire 1)' : '' }}</div>
            <div class="zone">
                @if($signatureDirectrice)
                    <img src="{{ $signatureDirectrice }}" style="height: 80px;" alt="">
                @endif
                @if($cachet)
                    <img src="{{ $cachet }}" style="height: 112px;" alt="">
                @endif
            </div>
            <div class="signataire-dag">{{ config('entreprise.representant.nom') }}</div>
            <div class="muted">{{ config('entreprise.representant.qualite') }}, Digital Agency Group</div>
        </td>
    </tr>
</table>

</div>

<p class="muted" style="margin-top: 30px; font-size: 10px;">
    Généré par la plateforme Digital Agency Group © {{ now()->year }}.
</p>
</body>
</html>
