<?php

use App\Support\ContratPdf;

test('the contrat text is split into numbered sections and paragraphs', function () {
    $sections = ContratPdf::sections("Préambule du contrat.\n\n1. Objet\nDAG réalise la prestation.\nSelon la demande.\n\n2. Prix\nLe montant est dû.");

    expect($sections)->toBe([
        ['titre' => null, 'paragraphes' => ['Préambule du contrat.']],
        ['titre' => '1. Objet', 'paragraphes' => ['DAG réalise la prestation. Selon la demande.']],
        ['titre' => '2. Prix', 'paragraphes' => ['Le montant est dû.']],
    ]);
});

test('an empty contrat text gives no section', function () {
    expect(ContratPdf::sections(null))->toBe([])
        ->and(ContratPdf::sections("  \n "))->toBe([]);
});
