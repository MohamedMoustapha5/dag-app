<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat {{ $contrat->reference }}</title>
    <style>
        @page { margin: 54px 56px; }
        body { font-family: DejaVu Sans, sans-serif; color: #071f41; font-size: 12px; line-height: 1.6; }
        .brand { font-size: 12px; letter-spacing: 2px; color: #0166fe; font-weight: bold; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 6px 0 2px; }
        .ref { color: #5b6b82; font-size: 11px; }
        table.meta { width: 100%; border-collapse: collapse; margin: 18px 0; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #e3e8f0; vertical-align: top; }
        table.meta td.k { width: 32%; color: #5b6b82; }
        .contenu { margin-top: 14px; white-space: pre-wrap; }
        .sign { margin-top: 28px; border: 1px solid #1e9e6b; background: #e8f8f1; padding: 12px 16px; }
        .sign.contestee { border-color: #d64545; background: #fbeaea; }
        .sign strong { display: block; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="brand">DAG &mdash; Digital Agency Group</div>
<h1>{{ $objet }}</h1>
<div class="ref">Contrat {{ $contrat->reference }} &middot; Code de vérification {{ $contrat->code_verification }}</div>

<table class="meta">
    <tr><td class="k">Cocontractant</td><td>{{ $partie }}</td></tr>
    @if($contrat->montant !== null)
        <tr><td class="k">Montant</td><td>{{ number_format((float) $contrat->montant, 0, ',', ' ') }} FCFA</td></tr>
    @endif
    <tr><td class="k">Statut</td><td>{{ $statutLabel }}</td></tr>
</table>

@if($contrat->contenu)
    <div class="contenu">{{ $contrat->contenu }}</div>
@endif

<div class="sign {{ $contrat->signature_contestee ? 'contestee' : '' }}">
    <strong>{{ $contrat->signature_contestee ? 'Signature contestée' : 'Signé électroniquement' }}</strong>
    Signataire : {{ $contrat->signataire_nom ?? $partie }}<br>
    Date de signature : {{ $dateSignature }}<br>
    @if($contrat->ip_signature)
        Adresse IP : {{ $contrat->ip_signature }}<br>
    @endif
    Code de vérification : {{ $contrat->code_verification }}
</div>
</body>
</html>
