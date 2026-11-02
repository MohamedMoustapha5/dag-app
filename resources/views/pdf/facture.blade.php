<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->reference }}</title>
    <style>
        @page { margin: 54px 56px; }
        body { font-family: DejaVu Sans, sans-serif; color: #071f41; font-size: 12px; line-height: 1.6; }
        .brand { font-size: 12px; letter-spacing: 2px; color: #0166fe; font-weight: bold; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 6px 0 2px; }
        .ref { color: #5b6b82; font-size: 11px; }
        .paid { display: inline-block; margin-top: 10px; padding: 4px 14px; border: 2px solid #1e9e6b; color: #1e9e6b; font-weight: bold; letter-spacing: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th { text-align: left; background: #eaf1ff; padding: 8px; font-size: 11px; }
        td { padding: 8px; border-bottom: 1px solid #e3e8f0; vertical-align: top; }
        td.k { width: 32%; color: #5b6b82; }
        .total td { font-weight: bold; font-size: 14px; border-bottom: 2px solid #071f41; }
        .foot { margin-top: 36px; font-size: 10.5px; color: #5b6b82; }
    </style>
</head>
<body>
<div class="brand">DAG &mdash; Digital Agency Group</div>
<h1>Facture</h1>
<div class="ref">N° {{ $facture->reference }}</div>
<div class="paid">ACQUITTÉE</div>

<table>
    <tr><td class="k">Facturé à</td><td>{{ $destinataire }}</td></tr>
    <tr><td class="k">Objet</td><td>{{ $objet }}</td></tr>
    <tr><td class="k">Date de règlement</td><td>{{ $datePaiement }}</td></tr>
    @if($facture->moyen_paiement)
        <tr><td class="k">Moyen de paiement</td><td>{{ $facture->moyen_paiement }}</td></tr>
    @endif
</table>

<table>
    <thead><tr><th>Désignation</th><th style="text-align:right">Montant</th></tr></thead>
    <tbody>
        <tr><td>{{ $objet }}</td><td style="text-align:right">{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</td></tr>
        <tr class="total"><td>Total réglé</td><td style="text-align:right">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
    </tbody>
</table>

<div class="foot">Document généré par DAG &mdash; Digital Agency Group. Cette facture est acquittée : aucun solde restant dû.</div>
</body>
</html>
