<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->reference }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: sans-serif; color: #071f41; font-size: 11.5px; line-height: 1.5; }
        .page { padding: 46px 52px 0 52px; }
        .bleu { color: #0166fe; }
        .muted { color: #5b6b82; }
        table { border-collapse: collapse; }
        .entete td { vertical-align: top; }
        .titre { font-size: 44px; font-weight: bold; color: #0166fe; letter-spacing: 1px; text-align: right; line-height: 1; }
        .meta { text-align: right; font-size: 11px; margin-top: 8px; }
        .meta span { color: #5b6b82; }
        .client-nom { font-size: 19px; font-weight: bold; color: #0166fe; margin: 2px 0 4px; }
        .lignes { width: 100%; margin-top: 30px; }
        .lignes th { background: #0166fe; color: #fff; padding: 9px 10px; font-size: 11px; letter-spacing: 0.5px; text-align: left; border: 1px solid #0166fe; }
        .lignes td { padding: 11px 10px; border: 1px solid #c9d3e3; font-size: 12px; }
        .c { text-align: center; }
        .r { text-align: right; }
        .totaux { width: 260px; margin-top: 14px; float: right; }
        .totaux td { padding: 3px 0; font-size: 12px; }
        .grand td { padding-top: 6px; }
        .grand .etiquette { background: #071f41; color: #fff; padding: 6px 10px; font-weight: bold; }
        .grand .montant { background: #0166fe; color: #fff; padding: 6px 10px; font-weight: bold; text-align: right; }
        .section-titre { font-size: 14px; font-weight: bold; color: #0166fe; margin: 0 0 6px; }
        .bas { clear: both; padding-top: 26px; }
        .tag { display: inline-block; border: 2px solid #1e9e6b; color: #1e9e6b; font-weight: bold; letter-spacing: 2px; padding: 2px 12px; font-size: 11px; }
        .pied { position: absolute; left: 52px; right: 52px; bottom: 40px; background: #0166fe; color: #fff; border-radius: 10px; padding: 11px 18px; font-size: 10.5px; }
        .pied td { color: #fff; }
        .signature { width: 210px; float: right; text-align: center; margin-top: 4px; }
        .signature .ligne { border-top: 1px solid #071f41; padding-top: 4px; font-size: 11px; }
    </style>
</head>
<body>
<div class="page">
    <table class="entete" width="100%">
        <tr>
            <td width="50%"><img src="{{ public_path('images/brand/dag-logo-pdf.png') }}" style="width: 190px;" alt="DAG — Digital Agency Group"></td>
            <td width="50%">
                <div class="titre">FACTURE</div>
                <div class="meta">
                    <span>N°</span> <strong>{{ $facture->reference }}</strong><br>
                    <span>Date de règlement :</span> {{ $datePaiement }}<br>
                    <span>Statut :</span> <strong style="color:#1e9e6b">Acquittée</strong>
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" style="margin-top: 30px;">
        <tr>
            <td>
                <div style="font-size: 14px; font-weight: bold;">Facturé à :</div>
                <div class="client-nom">{{ $destinataire }}</div>
                @if($contact)
                    <div style="font-size: 11px;">{{ $contact }}</div>
                @endif
            </td>
            <td class="r" style="vertical-align: bottom;">
                <span class="tag">PAYÉE</span>
            </td>
        </tr>
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th style="width: 7%;">N°</th>
                <th>DÉSIGNATION</th>
                <th class="c" style="width: 17%;">PRIX</th>
                <th class="c" style="width: 8%;">QTÉ</th>
                <th class="c" style="width: 17%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="c">1</td>
                <td>{{ $objet }}</td>
                <td class="c">{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</td>
                <td class="c">1</td>
                <td class="c">{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <table class="totaux">
        <tr><td>Sous-total</td><td class="r">{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td>Déjà réglé</td><td class="r">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td>Reste dû</td><td class="r">{{ number_format(max(0, (float) $facture->montant - (float) $facture->montant_paye), 0, ',', ' ') }} FCFA</td></tr>
        <tr class="grand">
            <td class="etiquette">Total réglé</td>
            <td class="montant">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    <div class="bas">
        <table width="55%" style="float:left;">
            <tr><td>
                <div class="section-titre">Informations de paiement</div>
                <table style="font-size: 11px;">
                    <tr><td class="muted" style="padding-right: 14px;">Moyen de paiement</td><td>{{ $moyen }}</td></tr>
                    <tr><td class="muted">Date de règlement</td><td>{{ $datePaiement }}</td></tr>
                    <tr><td class="muted">Référence</td><td>{{ $facture->reference }}</td></tr>
                </table>
            </td></tr>
            <tr><td style="padding-top: 22px;">
                <div class="section-titre">Conditions</div>
                <div style="font-size: 10.5px; color: #3d4f6b;">
                    Cette facture est acquittée : aucun solde n'est dû. Elle fait foi du paiement
                    des prestations désignées ci-dessus, effectuées par DAG — Digital Agency Group.
                </div>
            </td></tr>
            <tr><td style="padding-top: 22px;">
                <div style="font-size: 15px; font-weight: bold; color: #0166fe;">Merci pour votre confiance.</div>
            </td></tr>
        </table>

        <div class="signature">
            @if($signature)
                <img src="{{ $signature }}" alt="" style="height: 54px;">
            @else
                <div style="height: 54px;"></div>
            @endif
            <div class="ligne">Signature autorisée</div>
        </div>
    </div>
</div>

<div class="pied">
    <table width="100%">
        <tr>
            <td>contact@dag.cm</td>
            <td class="c">DAG — Digital Agency Group</td>
            <td class="r">Douala, Cameroun</td>
        </tr>
    </table>
</div>
</body>
</html>
