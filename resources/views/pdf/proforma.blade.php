<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Proforma {{ $numero }}</title>
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
        .titre { font-size: 36px; font-weight: bold; letter-spacing: 3px; color: #0b1a33; text-align: right; line-height: 1; }
        .numero { font-size: 15px; font-weight: bold; text-align: right; margin-top: 14px; }
        .date { font-size: 12px; color: #5b6478; text-align: right; margin-top: 5px; }
        .nature-doc { font-size: 11px; font-weight: bold; letter-spacing: 1.5px; color: #b07a00; text-align: right; margin-top: 6px; }
        .filet { height: 3px; background: #faa608; margin: 20px 0 32px; }

        .etiquette { font-size: 10.5px; font-weight: bold; letter-spacing: 1.5px; color: #b07a00; margin-bottom: 7px; }
        .partie-nom { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
        .partie { font-size: 12.5px; line-height: 1.6; }
        .partie-petit { font-size: 10.5px; color: #5b6478; margin-top: 3px; }

        /* Item grid: every cell framed, like a classic quote form. */
        .grille { margin-top: 30px; border: 1.5px solid #0b1a33; }
        .grille th { background: #0b1a33; color: #ffffff; font-size: 10px; font-weight: bold; letter-spacing: 1.2px; padding: 10px 12px; border: 1px solid #0b1a33; }
        .grille td { padding: 11px 12px; border: 1px solid #b9c2d3; font-size: 12px; vertical-align: top; }
        .grille tr.vide td { height: 22px; padding: 0 12px; }
        .grille tr.zebre td { background: #f6f8fc; }
        .objet { font-size: 12.5px; font-weight: bold; }
        .objet-detail { font-size: 10px; color: #5b6478; margin-top: 2px; }
        .objet-message { font-size: 10.5px; color: #3d4a63; margin-top: 6px; line-height: 1.5; }
        .chiffre { text-align: right; white-space: nowrap; }

        .bas-grille { margin-top: 0; }
        .ref { font-size: 12.5px; font-weight: bold; margin-bottom: 12px; }
        .totaux { border-collapse: collapse; }
        .totaux td { padding: 8px 12px; font-size: 12px; border: 1px solid #b9c2d3; }
        .totaux td.libelle { text-align: right; color: #3d4a63; font-weight: bold; }
        .totaux tr.total td { background: #0b1a33; border-color: #0b1a33; color: #ffffff; font-size: 13px; font-weight: bold; padding: 11px 12px; }
        .totaux tr.total td.chiffre { color: #faa608; font-size: 15px; }

        .note { margin-top: 30px; background: #eef1f8; border-left: 4px solid #faa608; padding: 13px 20px; }
        .note-titre { font-size: 12.5px; font-weight: bold; }
        .note-texte { font-size: 11px; color: #3d4a63; margin-top: 3px; }

        .validation { margin-top: 18px; border: 1.5px solid #16a34a; border-radius: 9px; padding: 12px 18px; }
        .validation-titre { font-size: 12.5px; font-weight: bold; color: #15803d; }
        .validation-texte { font-size: 11px; color: #3d4a63; margin-top: 2px; }
        .a-valider { margin-top: 18px; border: 1.5px dashed #b07a00; border-radius: 9px; padding: 12px 18px; }
        .a-valider-titre { font-size: 12.5px; font-weight: bold; color: #b07a00; }

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
                <div class="titre">PROFORMA</div>
                <div class="numero">N° {{ $numero }}</div>
                <div class="date">Émise le {{ $dateEmission }}</div>
                <div class="nature-doc">DEVIS · DEMANDE {{ $demande->reference }}</div>
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
                <div class="partie-nom">{{ $client['nom'] }}</div>
                <div class="partie">
                    {{ $client['type'] }}
                    @if($client['telephone'])
                        <br>{{ $client['telephone'] }}
                    @endif
                </div>
                @if($client['email'])
                    <div class="partie-petit">{{ $client['email'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Columns: 50 % + 12 % on the left, then two 19 % amount columns the totals line up under. --}}
    <table class="grille">
        <thead>
            <tr>
                <th width="50%" style="text-align: left;">DÉSIGNATION</th>
                <th width="12%" class="c">QTÉ</th>
                <th width="19%" class="r">PRIX UNITAIRE (FCFA)</th>
                <th width="19%" class="r">TOTAL (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="objet">{{ $objet }}</div>
                    <div class="objet-detail">{{ $nature }}</div>
                    @if($demande->message_devis && trim($demande->message_devis) !== '')
                        <div class="objet-message">{{ $demande->message_devis }}</div>
                    @endif
                </td>
                <td class="c">1</td>
                <td class="chiffre">{{ number_format($montant, 0, ',', ' ') }}</td>
                <td class="chiffre"><strong>{{ number_format($montant, 0, ',', ' ') }}</strong></td>
            </tr>
            @for($ligne = 1; $ligne <= 3; $ligne++)
                <tr class="vide{{ $ligne % 2 === 1 ? ' zebre' : '' }}">
                    <td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="bas-grille">
        <tr>
            <td width="62%" style="padding: 16px 18px 0 0;">
                <div class="etiquette" style="color: #5b6478;">VALABLE JUSQU’AU</div>
                <div class="ref">{{ $validiteJusquau }}</div>
                <div class="etiquette" style="color: #5b6478;">RÈGLEMENT</div>
                <div class="ref" style="margin-bottom: 0;">En ligne : {{ $moyens }}</div>
            </td>
            <td width="38%">
                <table class="totaux">
                    <tr>
                        <td width="50%" class="libelle">Sous-total</td>
                        <td width="50%" class="chiffre">{{ number_format($montant, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td class="libelle">Autres frais</td>
                        <td class="chiffre">0</td>
                    </tr>
                    <tr class="total">
                        <td>Total à payer</td>
                        <td class="chiffre">{{ number_format($montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($valideLe)
        <div class="validation">
            <div class="validation-titre">Devis validé en ligne le {{ $valideLe }}</div>
            <div class="validation-texte">Par {{ $client['nom'] }}, depuis son espace client DAG. Le règlement de la facture lance la prestation.</div>
        </div>
    @else
        <div class="a-valider">
            <div class="a-valider-titre">À valider en ligne</div>
            <div class="note-texte">Validez ce devis depuis votre espace client DAG (Mes demandes), puis réglez la facture en ligne : la prestation démarre dès réception du paiement.</div>
        </div>
    @endif

    <div class="note">
        <div class="note-titre">{{ $emetteur['mention_tva'] ?: 'Facture proforma : ce document n’est pas une facture définitive.' }}</div>
        <div class="note-texte">
            @if($emetteur['mention_tva'])
                Facture proforma : ce document n’est pas une facture définitive.
            @endif
            Prix ferme pendant {{ \App\Support\ProformaPdf::VALIDITE_JOURS }} jours, payable intégralement avant le démarrage de la prestation. La facture acquittée est délivrée après paiement.
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
