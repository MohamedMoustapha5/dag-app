import { dictionnaire } from './dictionnaire';
import { motifs } from './motifs';

const MOIS: Record<string, string> = {
    'janv.': 'Jan',
    janvier: 'January',
    'févr.': 'Feb',
    février: 'February',
    mars: 'March',
    'avr.': 'Apr',
    avril: 'April',
    mai: 'May',
    juin: 'June',
    'juil.': 'Jul',
    juillet: 'July',
    août: 'August',
    'sept.': 'Sep',
    septembre: 'September',
    'oct.': 'Oct',
    octobre: 'October',
    'nov.': 'Nov',
    novembre: 'November',
    'déc.': 'Dec',
    décembre: 'December',
    janv: 'Jan',
    févr: 'Feb',
    avr: 'Apr',
    juil: 'Jul',
    sept: 'Sep',
    oct: 'Oct',
    nov: 'Nov',
    déc: 'Dec',
};

const MOTIF_MOIS = new RegExp(
    `(\\d{1,2}(?:er)?) (${Object.keys(MOIS)
        .map((mois) => mois.replace('.', '\\.'))
        .join('|')})(?=$|[\\s,.)])`,
    'gi',
);

function traduireDates(texte: string): string {
    return texte.replace(
        MOTIF_MOIS,
        (_, jour: string, mois: string) =>
            `${jour.replace('er', '')} ${MOIS[mois.toLowerCase()] ?? mois}`,
    );
}

function normaliser(texte: string): string {
    return texte.replace(/\s+/g, ' ').trim();
}

/**
 * English version of a French interface text, or the text itself when it has
 * no translation (names, amounts, content written by the team…).
 */
export function traduire(texte: string): string {
    const coeur = normaliser(texte);

    if (coeur === '') {
        return texte;
    }

    const debut = texte.slice(0, texte.indexOf(coeur[0]));
    const fin = texte.slice(texte.lastIndexOf(coeur[coeur.length - 1]) + 1);

    return debut + traduireCoeur(coeur) + fin;
}

function traduireCoeur(coeur: string): string {
    const exact = dictionnaire.get(coeur);

    if (exact !== undefined) {
        return exact;
    }

    for (const [motif, remplacer] of motifs) {
        const correspondance = coeur.match(motif);

        if (correspondance) {
            return remplacer(correspondance, traduireCoeur);
        }
    }

    // "Libellé :" and "Libellé *" share the translation of "Libellé".
    const suffixe = coeur.match(/^(.*?)(\s*[:*…]+)$/);

    if (suffixe) {
        const base = dictionnaire.get(suffixe[1]);

        if (base !== undefined) {
            return base + suffixe[2];
        }
    }

    const avecDates = traduireDates(coeur);

    return avecDates;
}
