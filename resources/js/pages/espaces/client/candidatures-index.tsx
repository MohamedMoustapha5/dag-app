import { Head, router } from '@inertiajs/react';
import candidatures from '@/routes/espace-client/candidatures';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import RepartitionDonut from '@/components/espaces/repartition-donut';

type Candidature = {
    id: string;
    apprenantNom: string;
    formationSuivie: string | null;
    offreTitre: string;
    statut: string;
};

type Props = {
    candidatures: Candidature[];
    repartition: {
        enEtude: number;
        retenues: number;
        refusees: number;
    };
};

export default function CandidaturesIndex({
    candidatures: candidaturesList,
    repartition,
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
            <Head title="Candidatures" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 24,
                }}
            >
                Candidatures
            </h1>

            <div className="grid-3">
                <div style={{ gridColumn: 'span 2' }}>
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
                                        <th>Offre</th>
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
                                                {candidature.offreTitre}
                                            </td>
                                            <td className="muted">
                                                {candidature.formationSuivie ??
                                                    '—'}
                                            </td>
                                            <td>
                                                <span
                                                    className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                                                >
                                                    {STATUT_LABEL[
                                                        candidature.statut
                                                    ] ?? candidature.statut}
                                                </span>
                                            </td>
                                            <td style={{ textAlign: 'right' }}>
                                                {candidature.statut ===
                                                    'EN_ETUDE' ||
                                                candidature.statut ===
                                                    'DEPOSEE' ? (
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            gap: 10,
                                                            justifyContent:
                                                                'flex-end',
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
                                                                padding:
                                                                    '6px 14px',
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
                                                        style={{
                                                            fontSize: 12.5,
                                                        }}
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
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Répartition
                    </p>
                    <RepartitionDonut
                        segments={[
                            {
                                label: 'En étude',
                                value: repartition.enEtude,
                                color: 'var(--dag-blue)',
                            },
                            {
                                label: 'Retenues',
                                value: repartition.retenues,
                                color: 'var(--dag-green)',
                            },
                            {
                                label: 'Refusées',
                                value: repartition.refusees,
                                color: 'var(--dag-red)',
                            },
                        ]}
                    />
                </div>
            </div>
        </>
    );
}
