import { changerLangue, ecouterLangue, langueActive } from './langue';
import { traduire } from './traduire';

/**
 * Translates the interface in the browser. React renders the French text; this
 * observer swaps it for its English version while English is active, and puts
 * the French text back when the visitor returns to French. It covers text,
 * placeholders, tooltips and the page title, including what React renders
 * later (navigation, modals, refreshed data).
 */

const ATTRIBUTS = ['placeholder', 'title', 'aria-label', 'alt'] as const;
const IGNORES = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'CODE']);

const origineTexte = new WeakMap<Node, string>();
const traduitTexte = new WeakMap<Node, string>();
const attributs = new WeakMap<
    Element,
    Map<string, { origine: string; traduit: string }>
>();

function ignore(noeud: Node): boolean {
    const element =
        noeud.nodeType === Node.ELEMENT_NODE
            ? (noeud as Element)
            : noeud.parentElement;

    if (!element) {
        return true;
    }

    return (
        IGNORES.has(element.tagName) ||
        element.closest('[translate="no"], [contenteditable="true"]') !== null
    );
}

function traiterTexte(noeud: Node): void {
    if (ignore(noeud)) {
        return;
    }

    const courant = noeud.nodeValue ?? '';

    // Our own write, not new content from React.
    if (traduitTexte.get(noeud) === courant) {
        return;
    }

    origineTexte.set(noeud, courant);
    traduitTexte.delete(noeud);

    if (langueActive() !== 'en') {
        return;
    }

    const traduit = traduire(courant);

    if (traduit !== courant) {
        traduitTexte.set(noeud, traduit);
        noeud.nodeValue = traduit;
    }
}

function traiterAttribut(element: Element, nom: string): void {
    if (ignore(element)) {
        return;
    }

    const valeur = element.getAttribute(nom);

    if (valeur === null) {
        return;
    }

    const memoire = attributs.get(element) ?? new Map();
    const connu = memoire.get(nom);

    if (connu?.traduit === valeur) {
        return;
    }

    memoire.delete(nom);
    attributs.set(element, memoire);

    if (langueActive() !== 'en') {
        return;
    }

    const traduit = traduire(valeur);

    if (traduit !== valeur) {
        memoire.set(nom, { origine: valeur, traduit });
        element.setAttribute(nom, traduit);
    }
}

function parcourir(racine: Node): void {
    if (racine.nodeType === Node.TEXT_NODE) {
        traiterTexte(racine);

        return;
    }

    if (racine.nodeType !== Node.ELEMENT_NODE) {
        return;
    }

    const element = racine as Element;

    if (ignore(element)) {
        return;
    }

    ATTRIBUTS.forEach((nom) => traiterAttribut(element, nom));
    element.childNodes.forEach(parcourir);
}

/** Puts every translated text back to its French original. */
function restaurer(racine: Node): void {
    if (racine.nodeType === Node.TEXT_NODE) {
        const traduit = traduitTexte.get(racine);

        if (traduit !== undefined && racine.nodeValue === traduit) {
            racine.nodeValue = origineTexte.get(racine) ?? traduit;
        }

        traduitTexte.delete(racine);

        return;
    }

    if (racine.nodeType !== Node.ELEMENT_NODE) {
        return;
    }

    const element = racine as Element;
    const memoire = attributs.get(element);

    memoire?.forEach(({ origine, traduit }, nom) => {
        if (element.getAttribute(nom) === traduit) {
            element.setAttribute(nom, origine);
        }
    });
    attributs.delete(element);
    element.childNodes.forEach(restaurer);
}

function appliquer(): void {
    if (langueActive() === 'en') {
        parcourir(document.documentElement);

        return;
    }

    restaurer(document.documentElement);
}

let installe = false;

export function installerTraducteur(): void {
    if (installe || typeof document === 'undefined') {
        return;
    }

    installe = true;
    document.documentElement.lang = langueActive();

    const observateur = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'characterData') {
                traiterTexte(mutation.target);
            } else if (mutation.type === 'attributes') {
                traiterAttribut(
                    mutation.target as Element,
                    mutation.attributeName ?? '',
                );
            } else {
                mutation.addedNodes.forEach(parcourir);
            }
        }
    });

    observateur.observe(document.documentElement, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: [...ATTRIBUTS],
    });

    // Confirmation dialogs are not part of the page: translate them too.
    const confirmerOrigine = window.confirm.bind(window);

    window.confirm = (message?: string) =>
        confirmerOrigine(
            langueActive() === 'en' && message ? traduire(message) : message,
        );

    ecouterLangue(appliquer);
    appliquer();
}

export { changerLangue };
