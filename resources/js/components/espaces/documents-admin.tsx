import { router } from '@inertiajs/react';
import { Download, Search } from 'lucide-react';
import { useState } from 'react';
import { statistiques } from '@/routes/espace-admin';

export type DocumentsData = {
    liste: {
        type: string;
        titre: string;
        partie: string;
        reference: string | null;
        date: string;
        url: string;
    }[];
    total: number;
    limite: number;
    types: { value: string; total: number }[];
    filtres: { q: string; type: string };
};

const TYPE_LABEL: Record<string, string> = {
    contrats: 'Contrats signés',
    attestations: 'Attestations',
    cv: 'CV et dossiers',
};

const TYPE_CHIP: Record<string, string> = {
    contrats: 'chip-blue',
    attestations: 'chip-green',
    cv: 'chip-gray',
};

function formaterDate(date: string): string {
    const [annee, mois, jour] = date.split('-');

    return annee && mois && jour ? `${jour}/${mois}/${annee}` : date;
}

export default function DocumentsAdmin({
    documents,
}: {
    documents: DocumentsData;
}) {
    const [recherche, setRecherche] = useState(documents.filtres.q);

    const filtrer = (q: string, type: string) => {
        router.get(
            statistiques().url,
            {
                onglet: 'documents',
                ...(q !== '' ? { q } : {}),
                ...(type !== '' ? { type } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toutes = documents.types.reduce((somme, type) => somme + type.total, 0);

    return (
        <div className="card" style={{ padding: 24 }}>
            <div style={{ marginBottom: 16 }}>
                <p style={{ fontSize: 15, fontWeight: 700 }}>
                    Documents
                </p>
                <p className="muted" style={{ fontSize: 12.5, marginTop: 2 }}>
                    Tous les documents téléchargeables au même endroit.
                </p>
            </div>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    gap: 12,
                    flexWrap: 'wrap',
                    marginBottom: 16,
                }}
            >
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    <button
                        type="button"
                        className={
                            documents.filtres.type === ''
                                ? 'btn-primary'
                                : 'btn-outline'
                        }
                        style={{ padding: '6px 14px', fontSize: 12.5 }}
                        onClick={() => filtrer(recherche, '')}
                    >
                        Tous ({toutes})
                    </button>
                    {documents.types.map((type) => (
                        <button
                            key={type.value}
                            type="button"
                            className={
                                documents.filtres.type === type.value
                                    ? 'btn-primary'
                                    : 'btn-outline'
                            }
                            style={{ padding: '6px 14px', fontSize: 12.5 }}
                            onClick={() => filtrer(recherche, type.value)}
                        >
                            {TYPE_LABEL[type.value] ?? type.value} ({type.total})
                        </button>
                    ))}
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        filtrer(recherche, documents.filtres.type);
                    }}
                    style={{ position: 'relative', width: 280 }}
                >
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
                        placeholder="Rechercher un nom, une référence"
                        aria-label="Rechercher un document"
                        value={recherche}
                        onChange={(event) => setRecherche(event.target.value)}
                        style={{ paddingLeft: 34, width: '100%' }}
                    />
                </form>
            </div>

            {documents.liste.length === 0 ? (
                <p className="data-table-empty">
                    Aucun document ne correspond.
                </p>
            ) : (
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Concerne</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {documents.liste.map((document) => (
                                <tr key={document.url}>
                                    <td>
                                        <p style={{ fontWeight: 600 }}>
                                            {document.titre}
                                        </p>
                                        {document.reference && (
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {document.reference}
                                            </p>
                                        )}
                                    </td>
                                    <td className="muted">{document.partie}</td>
                                    <td>
                                        <span
                                            className={`chip ${TYPE_CHIP[document.type] ?? 'chip-gray'}`}
                                        >
                                            {TYPE_LABEL[document.type] ??
                                                document.type}
                                        </span>
                                    </td>
                                    <td className="muted">
                                        {formaterDate(document.date)}
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        <a
                                            href={document.url}
                                            className="link-action"
                                            style={{
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                gap: 6,
                                            }}
                                        >
                                            <Download size={14} /> Télécharger
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {documents.total > documents.limite && (
                <p
                    className="muted"
                    style={{ fontSize: 12.5, marginTop: 12 }}
                >
                    Les {documents.limite} plus récents sur {documents.total}{' '}
                    sont affichés : affinez avec la recherche ou le type.
                </p>
            )}
        </div>
    );
}
