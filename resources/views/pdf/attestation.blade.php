<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Attestation {{ $attestation->reference }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #071f41; margin: 0; }
        .frame { margin: 22px; padding: 14px; border: 3px solid #071f41; height: 690px; }
        .inner { border: 1px solid #faa608; padding: 28px 32px; height: 602px; text-align: center; }
        .brand { font-size: 13px; letter-spacing: 3px; color: #0166fe; font-weight: bold; text-transform: uppercase; }
        h1 { font-size: 30px; margin: 26px 0 6px; letter-spacing: 2px; text-transform: uppercase; }
        .sub { font-size: 12px; color: #5b6b82; }
        .name { font-size: 26px; font-weight: bold; margin: 28px 0 8px; color: #0166fe; }
        .text { font-size: 13.5px; line-height: 1.7; margin: 0 40px; }
        .formation { font-size: 19px; font-weight: bold; margin: 14px 0; }
        .meta { margin-top: 36px; font-size: 11.5px; color: #5b6b82; line-height: 1.7; }
        .signature { margin-top: 34px; font-size: 12px; }
        .signature strong { display: block; margin-top: 4px; }
    </style>
</head>
<body>
<div class="frame">
    <div class="inner">
        <div class="brand">DAG &mdash; Digital Agency Group</div>
        <h1>Attestation de formation</h1>
        <div class="sub">Réf. {{ $attestation->reference }}</div>

        <p class="text" style="margin-top: 30px;">DAG &mdash; Digital Agency Group atteste que</p>
        <div class="name">{{ $apprenant }}</div>
        <p class="text">a suivi avec assiduité et mené à son terme la formation</p>
        <div class="formation">{{ $formation }}</div>
        @if($duree)
            <p class="text">Durée de la formation : {{ $duree }}</p>
        @endif

        <div class="signature">
            Fait le {{ $dateEmission }}<br>
            <strong>La Direction de DAG</strong>
        </div>

        <div class="meta">
            Attestation validée par l'équipe DAG avant délivrance.<br>
            Numéro de référence : {{ $attestation->reference }}
        </div>
    </div>
</div>
</body>
</html>
