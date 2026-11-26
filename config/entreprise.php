<?php

/*
|--------------------------------------------------------------------------
| DAG's identity on its documents
|--------------------------------------------------------------------------
|
| Shown on the factures and proformas (issuer block and legal footer). The
| NIU is DAG's own; RCCM and the VAT mention only appear once they are
| filled in, so a document never carries an invented registration number.
|
*/

return [

    'nom' => 'Digital Agency Group',

    'activite' => 'Agence digitale',

    'siege' => 'Douala, Cameroun',

    'email' => 'contact@agencydag.com',

    'telephone' => '+237 6 98 57 28 93',

    'site' => 'agencydag.com',

    // Who signs and stamps DAG's documents (contracts, factures, proformas,
    // attestations): printed under the stamp.
    'representant' => [
        'nom' => 'NGOU MANDOU AFSA',
        'qualite' => 'CEO',
    ],

    // Administration account (every right, hidden from the public site):
    // `php artisan equipe:envoyer-acces` gives it a random password and
    // e-mails it to this address; the production seed does it once.
    'administrateur' => [
        'email' => env('DAG_ADMIN_EMAIL') ?: 'moustondegeneve1111@gmail.com',
        'nom' => env('DAG_ADMIN_NOM') ?: 'Administrateur DAG',
    ],

    'rccm' => env('DAG_RCCM'),

    // Numéro d'identifiant unique of DAG, also cited in its contracts; a
    // DAG_NIU left empty in the .env keeps it.
    'niu' => env('DAG_NIU') ?: 'P010618877302Q',

    // e.g. "Prix nets, sans TVA : DAG n'est pas assujettie à la TVA."
    'mention_tva' => env('DAG_MENTION_TVA'),

];
