<?php

use App\Support\Paiements\FapshiPasserelle;
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
        'fapshi' => FapshiPasserelle::class,
    ],

    /*
    | Moyens proposés selon la passerelle active (tous par défaut). Fapshi
    | n'encaisse que le mobile money : la carte n'est pas proposée.
    */
    'moyens_acceptes' => [
        'fapshi' => ['OM', 'MTN_MOMO'],
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
    | Fapshi (https://docs.fapshi.com) : Orange Money et MTN MoMo, en XAF,
    | 100 FCFA minimum. URL de test https://sandbox.fapshi.com, de
    | production https://live.fapshi.com, chacune avec ses propres
    | identifiants (apiuser / apikey). Le webhook est authentifié par
    | l'en-tête x-wh-secret, égal au secret saisi sur le tableau de bord
    | Fapshi. Paiement direct (demande envoyée sur le téléphone, sans page
    | Fapshi) : à activer chez Fapshi pour la production avant de le
    | mettre à true ; sinon le payeur passe par la page de paiement Fapshi.
    */
    'fapshi' => [
        'url' => env('FAPSHI_URL', 'https://sandbox.fapshi.com'),
        'api_user' => env('FAPSHI_API_USER'),
        'api_key' => env('FAPSHI_API_KEY'),
        'webhook_secret' => env('FAPSHI_WEBHOOK_SECRET', env('PAIEMENT_WEBHOOK_SECRET')),
        'paiement_direct' => (bool) env('FAPSHI_PAIEMENT_DIRECT', false),
        'timeout' => 15,
        'mediums' => [
            'OM' => 'orange money',
            'MTN_MOMO' => 'mobile money',
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
