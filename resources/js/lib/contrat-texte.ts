/**
 * The text of a contract is stored as plain text: an optional preamble,
 * then numbered articles ("1. Titre" on its own line, followed by the
 * article's paragraphs, blank lines between paragraphs). It is the layout
 * ContratPdf::sections() turns into titled sections, so the same rule is
 * used here to edit it article by article.
 */

export type Article = { titre: string; texte: string };

export type TexteContrat = { preambule: string; articles: Article[] };

const TITRE_ARTICLE = /^\d+[.)]\s+(\S.*)$/;

export function decouperContrat(contenu: string | null): TexteContrat {
    const preambule: string[] = [];
    const articles: { titre: string; lignes: string[] }[] = [];

    for (const ligne of (contenu ?? '').replace(/\r\n?/g, '\n').split('\n')) {
        const nette = ligne.trim();
        const titre = nette.length <= 90 ? nette.match(TITRE_ARTICLE) : null;

        if (titre) {
            articles.push({ titre: titre[1].trim(), lignes: [] });

            continue;
        }

        const courant = articles[articles.length - 1];
        (courant ? courant.lignes : preambule).push(ligne);
    }

    return {
        preambule: preambule.join('\n').trim(),
        articles: articles.map((article) => ({
            titre: article.titre,
            texte: article.lignes.join('\n').trim(),
        })),
    };
}

/**
 * Writes the articles back as text, numbered from 1 in their current order.
 */
export function assemblerContrat({
    preambule,
    articles,
}: TexteContrat): string {
    return [
        preambule.trim(),
        ...articles.map(
            (article, index) =>
                `${index + 1}. ${article.titre.trim() || 'Article'}\n${article.texte.trim()}`,
        ),
    ]
        .filter((bloc) => bloc !== '')
        .join('\n\n');
}

/**
 * Values used to preview a modèle and the clauses as a client would read
 * them once drafted (the real values come from the demande).
 */
export const VALEURS_EXEMPLE: Record<string, string> = {
    '{{client}}': 'Aïcha Ngono',
    '{{service}}': 'Réalisation de badges',
    '{{formule}}': 'Croissance',
    '{{description}}':
        'Réalisation de 120 badges pour le personnel de la société X.',
    '{{montant}}': '60 000',
    '{{reference}}': 'DAG-DEM-2026-0001',
    '{{date}}': new Intl.DateTimeFormat('fr-FR').format(new Date()),
};

export function remplacerVariables(
    texte: string,
    valeurs: Record<string, string> = VALEURS_EXEMPLE,
): string {
    return Object.entries(valeurs).reduce(
        (resultat, [code, valeur]) => resultat.split(code).join(valeur),
        texte,
    );
}

/**
 * The contract a demande would get today from this modèle and the clause
 * library, filled with example values (mirrors ContratRedaction::pour()).
 */
export function apercuContrat(
    modeleContenu: string,
    clauses: { titre: string; contenu: string }[],
): string {
    return assemblerContrat({
        preambule: remplacerVariables(modeleContenu),
        articles: clauses.map((clause) => ({
            titre: clause.titre,
            texte: remplacerVariables(clause.contenu),
        })),
    });
}
