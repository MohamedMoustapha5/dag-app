<?php

use App\Support\Paiements\MonerooPasserelle;
use App\Support\Paiements\SimulationPasserelle;

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle de paiement en ligne
    |--------------------------------------------------------------------------
    |
    | "simulation" déroule tout le parcours (bouton Payer, statuts, reçu,
    | webhook) sans prestataire réel : à utiliser en développement et en
    | recette uniquement. "moneroo" encaisse réellement (Orange Money, MTN
    | MoMo, carte) via https://moneroo.io. Changer de prestataire consiste
    | à ajouter une classe implémentant App\Support\Paiements\PasserellePaiement
    | et à la déclarer ici.
    |
    */

    'passerelle' => env('PAIEMENT_PASSERELLE', 'simulation'),

    'passerelles' => [
        'simulation' => SimulationPasserelle::class,
        'moneroo' => MonerooPasserelle::class,
    ],

    /*
    | Secret partagé avec le prestataire actif pour signer ses notifications
    | (HMAC SHA-256 du corps ; en-tête X-Signature pour la simulation,
    | X-Moneroo-Signature pour Moneroo). Sans secret, le webhook refuse
    | toutes les requêtes. URL à déclarer chez le prestataire :
    | {APP_URL}/webhooks/paiements
    */
    'webhook_secret' => env('PAIEMENT_WEBHOOK_SECRET'),

    /*
    | Devise ISO des montants envoyés au prestataire (FCFA d'Afrique centrale).
    */
    'devise' => 'XAF',

    'moneroo' => [
        'url' => env('MONEROO_URL', 'https://api.moneroo.io/v1/'),
        'cle_secrete' => env('MONEROO_SECRET_KEY'),
        'timeout' => 15,

        /*
        | Moyens Moneroo proposés pour chaque moyen DAG
        | (https://docs.moneroo.io/payments/available-methods).
        */
        'methodes' => [
            'OM' => ['orange_cm'],
            'MTN_MOMO' => ['mtn_cm'],
            'CARTE' => ['card_xaf'],
        ],

        /*
        | Moyen Moneroo utilisé pour chaque moyen de retrait DAG
        | (https://docs.moneroo.io/payouts/available-methods).
        */
        'methodes_decaissement' => [
            'OM' => 'orange_cm',
            'MTN_MOMO' => 'mtn_cm',
        ],
    ],

    /*
    | Moyens proposés pour les retraits (décaissements) vers un compte
    | mobile money.
    */
    'moyens_decaissement' => [
        'OM' => 'Orange Money',
        'MTN_MOMO' => 'MTN Mobile Money',
    ],

    /*
    | Délai pendant lequel une demande de paiement en attente bloque une
    | nouvelle tentative sur la même facture (minutes).
    */
    'delai_attente_minutes' => 15,

    'moyens' => [
        'OM' => ['label' => 'Orange Money', 'mobile' => true],
        'MTN_MOMO' => ['label' => 'MTN Mobile Money', 'mobile' => true],
        'CARTE' => ['label' => 'Carte bancaire', 'mobile' => false],
    ],

];
