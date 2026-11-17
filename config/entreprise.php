<?php

/*
|--------------------------------------------------------------------------
| DAG's identity on its documents
|--------------------------------------------------------------------------
|
| Shown on the factures (issuer block and legal footer). RCCM, NIU and the
| VAT mention only appear once they are filled in, so a document never
| carries an invented registration number.
|
*/

return [

    'nom' => 'DAG — Digital Agency Group',

    'activite' => 'Agence digitale',

    'siege' => 'Douala, Cameroun',

    'email' => 'contact@agencydag.com',

    'telephone' => '+237 6 98 57 28 93',

    'site' => 'agencydag.com',

    'rccm' => env('DAG_RCCM'),

    'niu' => env('DAG_NIU'),

    // e.g. "Prix nets, sans TVA : DAG n'est pas assujettie à la TVA."
    'mention_tva' => env('DAG_MENTION_TVA'),

];
