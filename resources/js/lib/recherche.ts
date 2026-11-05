/**
 * Lowercases and strips accents, so "securite" finds "Sécurité" and
 * "eleve" finds "Élève".
 */
export function normaliser(texte: string | null | undefined): string {
    return (texte ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * Whether the search term appears in at least one of the given fields,
 * ignoring case, accents and surrounding spaces. An empty term matches all.
 */
export function correspond(
    terme: string,
    ...champs: (string | null | undefined)[]
): boolean {
    const recherche = normaliser(terme);

    if (recherche === '') {
        return true;
    }

    return champs.some((champ) => normaliser(champ).includes(recherche));
}
