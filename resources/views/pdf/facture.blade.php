<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->reference }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Helvetica, sans-serif; color: #0b1a33; font-size: 12px; line-height: 1.45; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; padding: 0; }
        .page { padding: 52px 62px 0 62px; }
        .muted { color: #5b6478; }
        .r { text-align: right; }
        .c { text-align: center; }

        .sous-logo { font-size: 11px; color: #5b6478; margin-top: 6px; }
        .titre { font-size: 40px; font-weight: bold; letter-spacing: 3px; color: #0b1a33; text-align: right; line-height: 1; }
        .numero { font-size: 15px; font-weight: bold; text-align: right; margin-top: 14px; }
        .date { font-size: 12px; color: #5b6478; text-align: right; margin-top: 5px; }
        .nature-doc { font-size: 11px; font-weight: bold; letter-spacing: 1.5px; color: #b07a00; text-align: right; margin-top: 6px; }
        .filet { height: 3px; background: #faa608; margin: 20px 0 32px; }

        .etiquette { font-size: 10.5px; font-weight: bold; letter-spacing: 1.5px; color: #b07a00; margin-bottom: 7px; }
        .partie-nom { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
        .partie { font-size: 12.5px; line-height: 1.6; }
        .partie-petit { font-size: 10.5px; color: #5b6478; margin-top: 3px; }

        .entete-lignes { margin-top: 36px; background: #0b1a33; border-radius: 9px; }
        .entete-lignes td { color: #ffffff; font-size: 10.5px; font-weight: bold; letter-spacing: 1.5px; padding: 11px 18px; }
        .ligne td { padding: 14px 18px 13px; border-bottom: 1px solid #e3e7ef; }
        .objet { font-size: 13.5px; font-weight: bold; }
        .objet-detail { font-size: 10.5px; color: #5b6478; margin-top: 3px; }
        .montant-ligne { font-size: 13.5px; font-weight: bold; }

        .resume { margin-top: 18px; }
        .ref { font-size: 13px; font-weight: bold; margin-bottom: 14px; }
        .facture-ligne td { padding: 6px 20px; font-size: 12.5px; }
        .total { margin-top: 8px; background: #0b1a33; border-radius: 9px; }
        .total td { padding: 14px 20px; color: #ffffff; vertical-align: middle; }
        .total-libelle { font-size: 14px; font-weight: bold; }
        .total td.total-montant { font-size: 20px; font-weight: bold; color: #faa608; text-align: right; }

        .note { margin-top: 30px; background: #eef1f8; border-left: 4px solid #faa608; padding: 13px 20px; }
        .note-titre { font-size: 12.5px; font-weight: bold; }
        .note-texte { font-size: 11px; color: #3d4a63; margin-top: 3px; }

        .signature { width: 200px; margin: 26px 0 0 auto; text-align: center; }
        .signature-trait { border-top: 1px solid #c7cedb; padding-top: 4px; font-size: 10px; color: #5b6478; }

        .pied { position: absolute; left: 62px; right: 62px; bottom: 34px; border-top: 1px solid #d9dee8; padding-top: 10px; text-align: center; font-size: 9.5px; color: #5b6478; line-height: 1.6; }
    </style>
</head>
<body>
<div class="page">
    <table>
        <tr>
            <td width="52%">
                <img src="{{ public_path('images/brand/pdf/dag-logo-fond-blanc.png') }}" style="width: 180px;" alt="{{ $emetteur['nom'] }}">
                <div class="sous-logo">{{ $emetteur['activite'] }} · {{ $emetteur['siege'] }}</div>
            </td>
            <td width="48%">
                <div class="titre">FACTURE</div>
                <div class="numero">N° {{ $facture->reference }}</div>
                <div class="date">Date : {{ $datePaiement }}</div>
                <div class="nature-doc">FACTURE ACQUITTÉE</div>
            </td>
        </tr>
    </table>

    <div class="filet"></div>

    <table>
        <tr>
            <td width="52%" style="padding-right: 20px;">
                <div class="etiquette">ÉMIS PAR</div>
                <div class="partie-nom">{{ $emetteur['nom'] }}</div>
                <div class="partie">
                    {{ $emetteur['activite'] }}<br>
                    {{ $emetteur['siege'] }}<br>
                    @if($emetteur['niu'])
                        NIU : {{ $emetteur['niu'] }}<br>
                    @endif
                    {{ $emetteur['email'] }}<br>
                    {{ $emetteur['telephone'] }}
                </div>
            </td>
            <td width="48%">
                <div class="etiquette">CLIENT</div>
                <div class="partie-nom">{{ $destinataire['nom'] }}</div>
                <div class="partie">
                    @if($destinataire['type'])
                        {{ $destinataire['type'] }}<br>
                    @endif
                </div>
                @if($destinataire['email'])
                    <div class="partie-petit">{{ $destinataire['email'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="entete-lignes">
        <tr>
            <td width="58%">DESCRIPTION</td>
            <td width="14%" class="c">QTÉ</td>
            <td width="28%" class="r">MONTANT</td>
        </tr>
    </table>
    <table>
        <tr class="ligne">
            <td width="58%">
                <div class="objet">{{ $objet }}</div>
                <div class="objet-detail">{{ $nature }}</div>
            </td>
            <td width="14%" class="c" style="font-size: 13px;">1</td>
            <td width="28%" class="r montant-ligne">{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    <table class="resume">
        <tr>
            <td width="50%" style="padding: 4px 18px 0;">
                <div class="etiquette" style="color: #5b6478;">RÉFÉRENCE DU PAIEMENT</div>
                <div class="ref">{{ $referencePaiement }}</div>
                <div class="etiquette" style="color: #5b6478;">MOYEN DE PAIEMENT</div>
                <div class="ref" style="margin-bottom: 0;">{{ $moyen }}</div>
            </td>
            <td width="50%">
                <table>
                    <tr class="facture-ligne">
                        <td>Montant facturé</td>
                        <td class="r"><strong>{{ number_format((float) $facture->montant, 0, ',', ' ') }} FCFA</strong></td>
                    </tr>
                </table>
                <table class="total">
                    <tr>
                        <td class="total-libelle">Total payé</td>
                        <td class="total-montant">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="note">
        <div class="note-titre">{{ $emetteur['mention_tva'] ?: 'Facture acquittée : aucun solde n’est dû.' }}</div>
        <div class="note-texte">
            @if($emetteur['mention_tva'])
                Facture acquittée : aucun solde n’est dû.
            @endif
            Elle fait foi du paiement de la prestation désignée ci-dessus. Merci pour votre confiance.
        </div>
    </div>

    @if($cachet || $signature)
        <div class="signature">
            <div style="height: 76px;">
                @if($cachet)
                    <img src="{{ $cachet }}" alt="" style="height: 72px;">
                @endif
                @if($signature)
                    <img src="{{ $signature }}" alt="" style="height: 48px;">
                @endif
            </div>
            <div class="signature-trait">Pour {{ $emetteur['nom'] }}</div>
        </div>
    @endif
</div>

<div class="pied">
    {{ $emetteur['nom'] }} · Siège : {{ $emetteur['siege'] }}
    @if($emetteur['rccm'])
        · RCCM : {{ $emetteur['rccm'] }}
    @endif
    @if($emetteur['niu'])
        · NIU : {{ $emetteur['niu'] }}
    @endif
    <br>
    {{ $emetteur['site'] }} · {{ $emetteur['email'] }} · {{ $emetteur['telephone'] }}
</div>
</body>
</html>
