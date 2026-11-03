<?php

use App\Support\Paiements\SimulationPasserelle;

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle de paiement en ligne
    |--------------------------------------------------------------------------
    |
    | "simulation" déroule tout le parcours (bouton Payer, statuts, reçu,
    | webhook) sans prestataire réel : à utiliser en développement et en
    | recette uniquement. Le branchement d'un vrai prestataire (Orange Money,
    | MTN MoMo, carte) consiste à ajouter une classe implémentant
    | App\Support\Paiements\PasserellePaiement et à la déclarer ici.
    |
    */

    'passerelle' => env('PAIEMENT_PASSERELLE', 'simulation'),

    'passerelles' => [
        'simulation' => SimulationPasserelle::class,
    ],

    /*
    | Secret partagé avec le prestataire pour signer ses notifications
    | (HMAC SHA-256 du corps, en-tête X-Signature). Sans secret, le webhook
    | refuse toutes les requêtes.
    */
    'webhook_secret' => env('PAIEMENT_WEBHOOK_SECRET'),

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
