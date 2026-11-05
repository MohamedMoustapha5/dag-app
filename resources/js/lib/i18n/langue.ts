export type Langue = 'fr' | 'en';

const COOKIE = 'dag_lang';
const UN_AN = 60 * 60 * 24 * 365;

type Ecouteur = () => void;

const ecouteurs = new Set<Ecouteur>();

function lireCookie(): Langue {
    if (typeof document === 'undefined') {
        return 'fr';
    }

    const trouve = document.cookie
        .split('; ')
        .find((ligne) => ligne.startsWith(`${COOKIE}=`));

    return trouve?.split('=')[1] === 'en' ? 'en' : 'fr';
}

let courante: Langue = lireCookie();

export function langueActive(): Langue {
    return courante;
}

/**
 * Switches the language of the interface. The choice is kept in a cookie so it
 * survives reloads and is readable by the server (`<html lang>`).
 */
export function changerLangue(langue: Langue): void {
    courante = langue;
    document.cookie = `${COOKIE}=${langue}; path=/; max-age=${UN_AN}; SameSite=Lax`;
    document.documentElement.lang = langue;
    ecouteurs.forEach((ecouteur) => ecouteur());
}

export function ecouterLangue(ecouteur: Ecouteur): () => void {
    ecouteurs.add(ecouteur);

    return () => {
        ecouteurs.delete(ecouteur);
    };
}
