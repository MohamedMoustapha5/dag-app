<?php

use App\Support\Messagerie\JournalPasserelle;

return [

    /*
    |--------------------------------------------------------------------------
    | Canaux de notification
    |--------------------------------------------------------------------------
    |
    | L'e-mail est le canal actif. Le SMS et WhatsApp sont déjà branchés dans
    | l'architecture (canaux, journal, préférences) mais désactivés : les activer
    | demande seulement un fournisseur (voir "messagerie" ci-dessous) et le
    | passage du canal à true.
    |
    */

    'canaux' => [
        'email' => true,
        'sms' => (bool) env('NOTIFICATIONS_SMS', false),
        'whatsapp' => (bool) env('NOTIFICATIONS_WHATSAPP', false),
    ],

    /*
    | Fournisseurs de messagerie courte. "journal" écrit le message dans les logs ;
    | un vrai fournisseur est une classe implémentant
    | App\Support\Messagerie\PasserelleMessage, déclarée ici.
    */
    'messagerie' => [
        'sms' => env('NOTIFICATIONS_SMS_FOURNISSEUR', 'journal'),
        'whatsapp' => env('NOTIFICATIONS_WHATSAPP_FOURNISSEUR', 'journal'),
        'fournisseurs' => [
            'journal' => JournalPasserelle::class,
        ],
    ],

    /*
    | Connexion de file d'attente des notifications. "sync" envoie pendant la
    | requête (aucun worker nécessaire) ; en production, utiliser "database" ou
    | "redis" avec un worker pour ne jamais ralentir l'utilisateur.
    */
    'file_attente' => env('NOTIFICATIONS_QUEUE', 'sync'),

    /*
    | Relances : délai (jours) avant de relancer un contrat envoyé et non signé,
    | puis entre deux relances.
    */
    'relance_contrat_jours' => 3,

];
