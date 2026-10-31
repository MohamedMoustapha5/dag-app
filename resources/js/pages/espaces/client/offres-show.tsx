import { Head, Link, router } from '@inertiajs/react';
import offres from '@/routes/espace-client/offres';
import candidatures from '@/routes/espace-client/candidatures';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Candidature = {
    id: string;
    apprenantNom: string;
    formationSuivie: string | null;
    statut: string;
};

type Props = {
    offre: {
        id: string;
        titre: string;
        description: string;
        statut: string;
        typeContrat: string;
        ville: string | null;
        dateLimite: string | null;
    };
    candidatures: Candidature[];
};

export default function OffresShow({
    offre,
    candidatures: candidaturesList,
}: Props) {
    const decider = (candidatureId: string, statut: 'RETENUE' | 'REFUSEE') => {
        router.patch(
            candidatures.update(candidatureId).url,
            { statut },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={offre.titre} />

            <Link
                href={offres.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes mes offres
            </Link>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                        {offre.titre}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        {offre.typeContrat}
                        {offre.ville && ` · ${offre.ville}`}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[offre.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {offre.statut === 'PUBLIEE'
                        ? 'Active'
                        : (STATUT_LABEL[offre.statut] ?? offre.statut)}
                </span>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    className="muted"
                    style={{
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        whiteSpace: 'pre-line',
                    }}
                >
                    {offre.description}
                </p>
            </div>

            <h2 style={{ fontSize: 16, marginBottom: 12 }}>
                Candidatures ({candidaturesList.length})
            </h2>

            {candidaturesList.length === 0 ? (
                <p className="data-table-empty">
                    Aucune candidature pour le moment.
                </p>
            ) : (
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Apprenant</th>
                                <th>Formation suivie</th>
                                <th>Statut</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {candidaturesList.map((candidature) => (
                                <tr key={candidature.id}>
                                    <td style={{ fontWeight: 600 }}>
                                        {candidature.apprenantNom}
                                    </td>
                                    <td className="muted">
                                        {candidature.formationSuivie ?? '—'}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[candidature.statut] ??
                                                candidature.statut}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        {candidature.statut === 'EN_ETUDE' ||
                                        candidature.statut === 'DEPOSEE' ? (
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 10,
                                                    justifyContent: 'flex-end',
                                                }}
                                            >
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                    }}
                                                    onClick={() =>
                                                        decider(
                                                            candidature.id,
                                                            'REFUSEE',
                                                        )
                                                    }
                                                >
                                                    Refuser
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn-primary"
                                                    style={{
                                                        padding: '6px 14px',
                                                        fontSize: 12.5,
                                                    }}
                                                    onClick={() =>
                                                        decider(
                                                            candidature.id,
                                                            'RETENUE',
                                                        )
                                                    }
                                                >
                                                    Sélectionner
                                                </button>
                                            </div>
                                        ) : (
                                            <span
                                                className="muted"
                                                style={{ fontSize: 12.5 }}
                                            >
                                                Décision prise
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
}
