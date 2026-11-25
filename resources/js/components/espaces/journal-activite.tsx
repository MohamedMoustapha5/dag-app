import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { statistiques } from '@/routes/espace-admin';

export type JournalData = {
    entrees: {
        id: string;
        auteur: string | null;
        role: string | null;
        action: string;
        description: string | null;
        date: string | null;
    }[];
    page: number;
    dernierePage: number;
    total: number;
    precedente: string | null;
    suivante: string | null;
    membres: { id: string; nom: string }[];
    filtres: { q: string; auteur: string };
};

function initiales(nom: string | null): string {
    if (nom === null) {
        return 'SY';
    }

    return nom
        .split(' ')
        .map((mot) => mot[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function JournalActivite({ journal }: { journal: JournalData }) {
    const [recherche, setRecherche] = useState(journal.filtres.q);

    const filtrer = (q: string, auteur: string) => {
        router.get(
            statistiques().url,
            {
                onglet: 'journal',
                ...(q !== '' ? { q } : {}),
                ...(auteur !== '' ? { auteur } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <div className="card" style={{ padding: 24 }}>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 18,
                }}
            >
                <div>
                    <p style={{ fontSize: 15, fontWeight: 700 }}>
                        Journal d'activité
                    </p>
                    <p
                        className="muted"
                        style={{ fontSize: 12.5, marginTop: 2 }}
                    >
                        {journal.total} événement
                        {journal.total > 1 ? 's' : ''}, du plus récent au plus
                        ancien.
                    </p>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        filtrer(recherche, journal.filtres.auteur);
                    }}
                    style={{
                        display: 'flex',
                        gap: 10,
                        flexWrap: 'wrap',
                        alignItems: 'center',
                    }}
                >
                    <select
                        aria-label="Filtrer par auteur"
                        style={{ width: 210 }}
                        value={journal.filtres.auteur}
                        onChange={(event) =>
                            filtrer(recherche, event.target.value)
                        }
                    >
                        <option value="">Tous les auteurs</option>
                        <option value="systeme">Système et clients</option>
                        {journal.membres.map((membre) => (
                            <option key={membre.id} value={membre.id}>
                                {membre.nom}
                            </option>
                        ))}
                    </select>
                    <div style={{ position: 'relative', width: 230 }}>
                        <Search
                            size={15}
                            style={{
                                position: 'absolute',
                                left: 11,
                                top: '50%',
                                transform: 'translateY(-50%)',
                                color: 'var(--dag-text-muted)',
                            }}
                        />
                        <input
                            type="search"
                            placeholder="Rechercher"
                            aria-label="Rechercher dans le journal"
                            value={recherche}
                            onChange={(event) =>
                                setRecherche(event.target.value)
                            }
                            style={{ paddingLeft: 34, width: '100%' }}
                        />
                    </div>
                </form>
            </div>

            {journal.entrees.length === 0 ? (
                <p className="data-table-empty">
                    Aucun événement ne correspond.
                </p>
            ) : (
                <ul
                    style={{
                        listStyle: 'none',
                        margin: 0,
                        padding: 0,
                        display: 'flex',
                        flexDirection: 'column',
                    }}
                >
                    {journal.entrees.map((entree) => (
                        <li
                            key={entree.id}
                            style={{
                                display: 'flex',
                                gap: 14,
                                alignItems: 'flex-start',
                                padding: '13px 0',
                                borderTop: '1px solid var(--dag-border)',
                            }}
                        >
                            <span
                                aria-hidden
                                style={{
                                    flex: 'none',
                                    width: 34,
                                    height: 34,
                                    borderRadius: 999,
                                    display: 'grid',
                                    placeItems: 'center',
                                    fontSize: 11.5,
                                    fontWeight: 800,
                                    background:
                                        entree.auteur === null
                                            ? '#f1f2f6'
                                            : 'var(--dag-blue-light)',
                                    color:
                                        entree.auteur === null
                                            ? 'var(--dag-text-muted)'
                                            : 'var(--dag-blue)',
                                }}
                            >
                                {initiales(entree.auteur)}
                            </span>
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <p style={{ fontSize: 13.5, lineHeight: 1.5 }}>
                                    <strong>
                                        {entree.auteur ?? 'Système'}
                                    </strong>{' '}
                                    {entree.action}
                                </p>
                                {entree.description && (
                                    <p
                                        className="muted"
                                        style={{
                                            fontSize: 12.5,
                                            marginTop: 2,
                                            overflowWrap: 'anywhere',
                                        }}
                                    >
                                        {entree.description}
                                    </p>
                                )}
                            </div>
                            <div
                                className="muted"
                                style={{
                                    flex: 'none',
                                    textAlign: 'right',
                                    fontSize: 12,
                                }}
                            >
                                {entree.date}
                                {entree.role && (
                                    <span style={{ display: 'block' }}>
                                        {entree.role}
                                    </span>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {journal.dernierePage > 1 && (
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginTop: 16,
                        paddingTop: 16,
                        borderTop: '1px solid var(--dag-border)',
                    }}
                >
                    <button
                        type="button"
                        className="btn-outline"
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        disabled={journal.precedente === null}
                        onClick={() =>
                            journal.precedente &&
                            router.visit(journal.precedente, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Précédent
                    </button>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Page {journal.page} sur {journal.dernierePage}
                    </span>
                    <button
                        type="button"
                        className="btn-outline"
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        disabled={journal.suivante === null}
                        onClick={() =>
                            journal.suivante &&
                            router.visit(journal.suivante, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Suivant
                    </button>
                </div>
            )}
        </div>
    );
}
