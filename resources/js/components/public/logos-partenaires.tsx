export type PartenairePublic = {
    nom: string;
    logo: string;
    lien: string | null;
    sombre: boolean;
};

/**
 * Enough tiles in one half of the band for it to be wider than the screen,
 * so the loop never shows a gap, however few logos there are.
 */
function repetitionsPour(nombre: number): number {
    return Math.max(1, Math.ceil(8 / Math.max(nombre, 1)));
}

/**
 * The scrolling band of the home page. It holds two identical halves and
 * slides by exactly one half; only the first set is read by screen readers
 * and reachable with the keyboard.
 */
export function BandeLogosPartenaires({
    partenaires,
}: {
    partenaires: PartenairePublic[];
}) {
    const repetitions = repetitionsPour(partenaires.length);

    return (
        <div className="logo-defilant">
            <div className="logo-defilant-piste">
                {[0, 1].map((copie) => (
                    <ul
                        key={copie}
                        className="logo-defilant-groupe"
                        aria-hidden={copie === 1 ? true : undefined}
                    >
                        {Array.from({ length: repetitions }, (_, tour) =>
                            partenaires.map((partenaire, index) => {
                                const original = copie === 0 && tour === 0;
                                const carte = (
                                    <div
                                        className={`logo-defilant-carte${partenaire.sombre ? ' logo-defilant-carte--sombre' : ''}`}
                                        title={partenaire.nom}
                                    >
                                        <img
                                            src={partenaire.logo}
                                            alt={original ? partenaire.nom : ''}
                                            loading="lazy"
                                            draggable={false}
                                        />
                                    </div>
                                );

                                return (
                                    <li
                                        key={`${tour}-${index}`}
                                        className={
                                            original
                                                ? undefined
                                                : 'logo-defilant-repetition'
                                        }
                                    >
                                        {partenaire.lien ? (
                                            <a
                                                href={partenaire.lien}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                tabIndex={
                                                    original ? undefined : -1
                                                }
                                                aria-label={
                                                    original
                                                        ? `${partenaire.nom} (nouvel onglet)`
                                                        : undefined
                                                }
                                            >
                                                {carte}
                                            </a>
                                        ) : (
                                            carte
                                        )}
                                    </li>
                                );
                            }),
                        )}
                    </ul>
                ))}
            </div>
        </div>
    );
}

/**
 * The still strip of the « À propos » page.
 */
export function BandeauLogosPartenaires({
    partenaires,
}: {
    partenaires: PartenairePublic[];
}) {
    return (
        <div className="card partner-logo-strip" style={{ marginTop: 32 }}>
            {partenaires.map((partenaire, index) => {
                const image = (
                    <img
                        src={partenaire.logo}
                        alt={partenaire.nom}
                        title={partenaire.nom}
                        loading="lazy"
                        className={`partner-logo-strip-item${partenaire.sombre ? ' partner-logo-strip-item--sombre' : ''}`}
                    />
                );

                return partenaire.lien ? (
                    <a
                        key={index}
                        href={partenaire.lien}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="partner-logo-strip-lien"
                    >
                        {image}
                    </a>
                ) : (
                    <span key={index} className="partner-logo-strip-lien">
                        {image}
                    </span>
                );
            })}
        </div>
    );
}
