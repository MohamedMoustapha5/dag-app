import { router, useHttp } from '@inertiajs/react';
import { Loader2, Search, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';

type Resultat = {
    type: string;
    titre: string;
    detail: string | null;
    url: string;
};

type Props = {
    /** JSON endpoint answering `?q=` with `{ resultats: Resultat[] }`. */
    url: string;
    placeholder: string;
};

const DELAI_MS = 250;

/**
 * Topbar search of an espace: results appear under the field as you type,
 * can be browsed with the arrow keys and open with Enter or a click.
 */
export default function RechercheEspace({ url, placeholder }: Props) {
    const [terme, setTerme] = useState('');
    const [resultats, setResultats] = useState<Resultat[]>([]);
    const [ouvert, setOuvert] = useState(false);
    const [actif, setActif] = useState(-1);
    const [termeCherche, setTermeCherche] = useState('');
    const conteneur = useRef<HTMLDivElement>(null);
    const derniereRecherche = useRef('');
    const listeId = useId();
    const http = useHttp<Record<string, never>, { resultats: Resultat[] }>();

    useEffect(() => {
        const recherche = terme.trim();
        derniereRecherche.current = recherche;

        if (recherche.length < 2) {
            http.cancel();
            setResultats([]);
            setTermeCherche('');

            return;
        }

        const minuteur = window.setTimeout(() => {
            http.cancel();
            http.get(`${url}?q=${encodeURIComponent(recherche)}`, {
                onSuccess: (reponse) => {
                    if (derniereRecherche.current !== recherche) {
                        return;
                    }

                    setResultats(reponse.resultats);
                    setTermeCherche(recherche);
                    setActif(reponse.resultats.length > 0 ? 0 : -1);
                },
            }).catch(() => {
                // A search overtaken by a newer one is ignored; a failed one
                // must not leave the list stuck on "Recherche…".
                if (derniereRecherche.current === recherche) {
                    setResultats([]);
                    setTermeCherche(recherche);
                }
            });
        }, DELAI_MS);

        return () => window.clearTimeout(minuteur);
    }, [terme, url]);

    useEffect(() => {
        const fermerSiDehors = (event: MouseEvent) => {
            if (!conteneur.current?.contains(event.target as Node)) {
                setOuvert(false);
            }
        };

        document.addEventListener('mousedown', fermerSiDehors);

        return () => document.removeEventListener('mousedown', fermerSiDehors);
    }, []);

    const ouvrir = (resultat: Resultat) => {
        setOuvert(false);
        setTerme('');
        router.visit(resultat.url);
    };

    const auClavier = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape') {
            setOuvert(false);
            event.currentTarget.blur();

            return;
        }

        if (resultats.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOuvert(true);
            setActif((index) => (index + 1) % resultats.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActif((index) =>
                index <= 0 ? resultats.length - 1 : index - 1,
            );
        } else if (event.key === 'Enter' && actif >= 0) {
            event.preventDefault();
            ouvrir(resultats[actif]);
        }
    };

    const assezLong = terme.trim().length >= 2;
    const enCours =
        assezLong && (http.processing || termeCherche !== terme.trim());

    return (
        <div className="espace-search" ref={conteneur}>
            <Search size={16} />
            <input
                type="text"
                className="espace-search-input"
                role="combobox"
                aria-expanded={ouvert && assezLong}
                aria-controls={listeId}
                aria-autocomplete="list"
                aria-activedescendant={
                    actif >= 0 ? `${listeId}-${actif}` : undefined
                }
                aria-label={placeholder}
                placeholder={placeholder}
                value={terme}
                onChange={(event) => {
                    setTerme(event.target.value);
                    setOuvert(true);
                }}
                onFocus={() => setOuvert(true)}
                onKeyDown={auClavier}
            />
            {terme !== '' && (
                <button
                    type="button"
                    className="espace-search-clear"
                    aria-label="Effacer la recherche"
                    onClick={() => setTerme('')}
                >
                    {enCours ? (
                        <Loader2 size={14} className="animate-spin" />
                    ) : (
                        <X size={14} />
                    )}
                </button>
            )}

            {ouvert && assezLong && (
                <div
                    className="espace-search-results"
                    id={listeId}
                    role="listbox"
                >
                    {enCours && resultats.length === 0 ? (
                        <p className="espace-search-empty">Recherche…</p>
                    ) : resultats.length === 0 ? (
                        <p className="espace-search-empty">
                            Aucun résultat pour « {terme.trim()} ».
                        </p>
                    ) : (
                        resultats.map((resultat, index) => (
                            <button
                                key={`${resultat.type}-${resultat.url}-${index}`}
                                id={`${listeId}-${index}`}
                                type="button"
                                role="option"
                                aria-selected={index === actif}
                                className={`espace-search-result${index === actif ? ' is-active' : ''}`}
                                onMouseEnter={() => setActif(index)}
                                onClick={() => ouvrir(resultat)}
                            >
                                <span className="espace-search-type">
                                    {resultat.type}
                                </span>
                                <span className="espace-search-text">
                                    <span className="espace-search-title">
                                        {resultat.titre}
                                    </span>
                                    {resultat.detail && (
                                        <span className="espace-search-detail">
                                            {resultat.detail}
                                        </span>
                                    )}
                                </span>
                            </button>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}
