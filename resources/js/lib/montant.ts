/**
 * The narrow no-break space toLocaleString('fr-FR') puts between thousands
 * is nearly invisible in the site font: it is swapped for a regular
 * no-break space ("20 000", not "20000").
 */
const ESPACE_FINE = new RegExp(String.fromCharCode(0x202f), 'g');
const ESPACE_INSECABLE = String.fromCharCode(0xa0);

export function separerMilliers(valeur: number): string {
    return Math.round(valeur)
        .toLocaleString('fr-FR')
        .replace(ESPACE_FINE, ESPACE_INSECABLE);
}

/**
 * An amount in FCFA as shown everywhere in the espaces: "20 000 FCFA"
 * (no decimals, French thousands separator), or "—" when there is none.
 */
export function formatMontant(
    montant: string | number | null | undefined,
): string {
    if (montant === null || montant === undefined || montant === '') {
        return '—';
    }

    const valeur = Number(montant);

    return Number.isNaN(valeur) ? '—' : `${separerMilliers(valeur)} FCFA`;
}
