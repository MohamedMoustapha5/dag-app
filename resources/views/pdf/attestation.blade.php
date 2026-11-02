<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Attestation {{ $attestation->reference }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: sans-serif; color: #071f41; }
        .abs { position: absolute; }
        .fond { left: 0; top: 0; width: 1123px; height: 794px; }
        .logo { left: 78px; top: 66px; width: 190px; }
        .titre { left: 150px; top: 200px; font-size: 62px; font-weight: bold; color: #0166fe; letter-spacing: 1px; }
        .sous-titre { left: 152px; top: 282px; font-size: 17px; font-weight: bold; letter-spacing: 3px; }
        .presente { left: 152px; top: 315px; font-size: 11px; letter-spacing: 2px; color: #5b6b82; }
        .nom { left: 150px; top: 372px; font-size: 40px; font-weight: bold; color: #0166fe; }
        .formation { left: 152px; top: 440px; width: 620px; font-size: 16px; font-weight: bold; }
        .texte { left: 152px; top: 478px; width: 620px; font-size: 12px; line-height: 1.7; color: #3d4f6b; }
        .date-bloc { left: 210px; top: 640px; width: 220px; font-size: 12px; }
        .signature-bloc { left: 500px; top: 540px; width: 250px; font-size: 12px; text-align: center; }
        .ligne { border-top: 1px solid #071f41; padding-top: 5px; letter-spacing: 2px; font-size: 10px; color: #5b6b82; }
        .sceau-1 { left: 855px; top: 214px; width: 100px; text-align: center; font-size: 10px; font-weight: bold; letter-spacing: 2px; color: #0166fe; }
        .sceau-2 { left: 855px; top: 232px; width: 100px; text-align: center; font-size: 28px; font-weight: bold; color: #071f41; }
        .sceau-3 { left: 855px; top: 266px; width: 100px; text-align: center; font-size: 9px; letter-spacing: 2px; color: #faa608; font-weight: bold; }
        .ref { left: 210px; top: 700px; font-size: 9.5px; color: #5b6b82; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <img class="abs fond" src="{{ public_path('images/brand/pdf/attestation-fond.svg') }}" alt="">
    <img class="abs logo" src="{{ public_path('images/brand/dag-logo-pdf.png') }}" alt="DAG — Digital Agency Group">

    <div class="abs titre">Attestation</div>
    <div class="abs sous-titre">DE FORMATION</div>
    <div class="abs presente">CETTE ATTESTATION EST DÉCERNÉE À</div>

    <div class="abs nom">{{ $apprenant }}</div>

    <div class="abs formation">{{ $formation }}</div>
    <div class="abs texte">
        DAG — Digital Agency Group atteste que la personne ci-dessus a suivi avec assiduité
        et mené à son terme cette formation @if($duree)(durée : {{ $duree }})@endif.
        Cette attestation a été vérifiée et validée par l'équipe DAG avant sa délivrance.
    </div>

    <div class="abs date-bloc">
        <div style="font-size: 13px; font-weight: bold; margin-bottom: 6px;">{{ $dateEmission }}</div>
        <div class="ligne">DATE</div>
    </div>

    <div class="abs signature-bloc">
        <div style="height: 104px;">
            @if($cachet)
                <img src="{{ $cachet }}" alt="" style="height: 100px;">
            @endif
            @if($signature)
                <img src="{{ $signature }}" alt="" style="height: 62px;">
            @endif
        </div>
        <div class="ligne">LA DIRECTION &mdash; SIGNATURE</div>
    </div>

    <div class="abs sceau-1">CERTIFIÉ</div>
    <div class="abs sceau-2">DAG</div>
    <div class="abs sceau-3">FORMATION</div>

    <div class="abs ref">Réf. {{ $attestation->reference }} &nbsp;·&nbsp; Délivrée par DAG — Digital Agency Group, Douala, Cameroun</div>
</body>
</html>
