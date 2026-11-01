import { Head, Link } from '@inertiajs/react';
import espaceApprenant from '@/routes/espace-apprenant';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Formation = {
    inscriptionId: string;
    formation: string;
    statut: string;
    progression: number;
    seancesTotal: number;
};

type Props = {
    formations: Formation[];
};

export default function ApprenantProgressionIndex({ formations }: Props) {
    return (
        <>
            <Head title="Progression" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/presentation-data-review.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Progression</h1>
                    <p>
                        Le suivi de chacune de vos formations, qu'elles soient
                        suivies en parallèle ou l'une après l'autre.
                    </p>
                </div>
            </div>

            {formations.length === 0 ? (
                <div
                    className="card"
                    style={{ padding: 32, textAlign: 'center' }}
                >
                    <p style={{ fontSize: 14, fontWeight: 700 }}>
                        Aucune formation en cours
                    </p>
                    <p className="muted" style={{ marginTop: 8, fontSize: 13 }}>
                        Inscrivez-vous à une session pour suivre votre
                        progression ici.
                    </p>
                    <Link
                        href={espaceApprenant.formations()}
                        className="btn-primary"
                        style={{ marginTop: 18, display: 'inline-flex' }}
                    >
                        Parcourir les formations
                    </Link>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    {formations.map((item) => (
                        <div
                            key={item.inscriptionId}
                            className="card"
                            style={{ padding: 24 }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 16,
                                    flexWrap: 'wrap',
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                    }}
                                >
                                    <h3
                                        style={{
                                            fontSize: 16,
                                            color: 'var(--dag-navy)',
                                        }}
                                    >
                                        {item.formation}
                                    </h3>
                                    <span
                                        className={`chip ${STATUT_CHIP[item.statut] ?? 'chip-gray'}`}
                                    >
                                        {STATUT_LABEL[item.statut] ??
                                            item.statut}
                                    </span>
                                </div>
                                <Link
                                    href={espaceApprenant.progression.show(
                                        item.inscriptionId,
                                    )}
                                    className="btn-outline"
                                >
                                    Voir le suivi
                                </Link>
                            </div>
                            <div style={{ marginTop: 16 }}>
                                <div
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        fontSize: 12,
                                    }}
                                >
                                    <span className="muted">
                                        {item.seancesTotal} séance(s) au total
                                    </span>
                                    <strong>{item.progression}%</strong>
                                </div>
                                <div className="espace-formule-progress">
                                    <div
                                        className="espace-formule-progress-bar"
                                        style={{
                                            width: `${item.progression}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
