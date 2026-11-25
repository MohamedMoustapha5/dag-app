/**
 * On phones, data tables are shown as cards (see "Data tables that turn into
 * cards" in dag.css): each cell displays its column name. Tables written with
 * their own labels (class "cartes-mobile") keep them; every other data table
 * gets them here, from its header row, so no list hides columns behind a
 * horizontal scroll. A table can opt out with data-cartes="non".
 *
 * The labels are read from the headers as currently displayed, so they follow
 * the interface language: the translator rewrites the headers, which triggers
 * a new pass.
 */

let installe = false;
let planifie = false;

function etiqueter(table: HTMLTableElement): void {
    if (
        table.classList.contains('cartes-mobile') ||
        table.dataset.cartes === 'non'
    ) {
        return;
    }

    const entetes = Array.from(table.tHead?.rows[0]?.cells ?? []).map(
        (cellule) => (cellule.textContent ?? '').replace(/\s+/g, ' ').trim(),
    );

    if (entetes.length === 0) {
        return;
    }

    if (!table.hasAttribute('data-cartes-auto')) {
        table.setAttribute('data-cartes-auto', '');
    }

    for (const corps of Array.from(table.tBodies)) {
        for (const ligne of Array.from(corps.rows)) {
            let colonne = 0;

            Array.from(ligne.cells).forEach((cellule, position) => {
                // The first cell is the title of the card; a cell spanning the
                // whole row (an empty-list message) is shown as is.
                const titre = position === 0;
                const libelle =
                    titre || cellule.colSpan > 1
                        ? ''
                        : (entetes[colonne] ?? '');

                cellule.classList.toggle('cellule-titre-auto', titre);

                if (cellule.getAttribute('data-label') !== libelle) {
                    cellule.setAttribute('data-label', libelle);
                }

                colonne += cellule.colSpan;
            });
        }
    }
}

function etiqueterTout(): void {
    planifie = false;
    document
        .querySelectorAll<HTMLTableElement>('table.data-table')
        .forEach(etiqueter);
}

function planifier(): void {
    if (!planifie) {
        planifie = true;
        requestAnimationFrame(etiqueterTout);
    }
}

export function installerTableauxMobiles(): void {
    if (installe || typeof document === 'undefined') {
        return;
    }

    installe = true;

    new MutationObserver(planifier).observe(document.documentElement, {
        childList: true,
        subtree: true,
        characterData: true,
    });

    planifier();
}
