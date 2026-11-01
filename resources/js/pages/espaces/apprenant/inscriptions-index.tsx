import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import espaceApprenant from '@/routes/espace-apprenant';

type Inscription = {
    id: string;
    formation: string;
    ville: string | null;
    mode: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    statut: string;
    progression: number;
    semainesAvantDebut: number | null;
};

type Props = {
    inscriptions: Inscription[];
};

type Onglet = 'toutes' | 'en-cours' | 'a-venir' | 'terminees';

const ONGLETS: { value: Onglet; label: string }[] = [
    { value: 'toutes', label: 'Toutes' },
    { value: 'en-cours', label: 'En cours' },
    { value: 'a-venir', label: 'À venir' },
    { value: 'terminees', label: 'Terminées' },
];

function classifier(inscription: Inscription): Onglet {
    if (inscription.statut === 'FORMEE') {
        return 'terminees';
    }
    if (inscription.semainesAvantDebut !== null) {
        return 'a-venir';
    }
    return 'en-cours';
}

export default function ApprenantInscriptionsIndex({ inscriptions }: Props) {
    const [onglet, setOnglet] = useState<Onglet>('toutes');

    const filtered = useMemo(
        () =>
            onglet === 'toutes'
                ? inscriptions
                : inscriptions.filter(
                      (inscription) => classifier(inscription) === onglet,
                  ),
        [inscriptions, onglet],
    );

    return (
        <>
            <Head title="Mes inscriptions" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/lms-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Mes inscriptions</h1>
                        <p>L'historique de vos sessions de formation DAG.</p>
                    </div>
                    <Link
                        href={espaceApprenant.formations()}
                        className="btn-primary"
                    >
                        + Parcourir les formations
                    </Link>
                </div>
            </div>

            <div className="account-tabs" style={{ marginBottom: 24 }}>
                {ONGLETS.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        className={`account-tab${onglet === item.value ? ' active' : ''}`}
                        onClick={() => setOnglet(item.value)}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            {filtered.length === 0 ? (
                <p className="data-table-empty">
                    Aucune inscription dans cette catégorie.
                </p>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    {filtered.map((inscription) => {
                        const classe = classifier(inscription);

                        return (
                            <div
                                key={inscription.id}
                                className="card"
                                style={{ padding: 24 }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'flex-start',
                                        gap: 16,
                                        flexWrap: 'wrap',
                                    }}
                                >
                                    <div>
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
                                                {inscription.formation}
                                            </h3>
                                            <span
                                                className={`chip ${STATUT_CHIP[inscription.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[
                                                    inscription.statut
                                                ] ?? inscription.statut}
                                            </span>
                                        </div>
                                        <p
                                            className="muted"
                                            style={{
                                                marginTop: 6,
                                                fontSize: 12.5,
                                            }}
                                        >
                                            Session du {inscription.dateDebut}
                                            {inscription.dateFin
                                                ? ` au ${inscription.dateFin}`
                                                : ''}
                                            {inscription.ville
                                                ? ` · ${inscription.ville}`
                                                : ''}
                                            {inscription.mode
                                                ? ` · ${inscription.mode}`
                                                : ''}
                                        </p>
                                    </div>

                                    {classe === 'terminees' && (
                                        <span
                                            className="btn-outline"
                                            style={{
                                                opacity: 0.5,
                                                cursor: 'not-allowed',
                                            }}
                                            title="Aperçu PDF bientôt disponible"
                                        >
                                            Voir l'attestation
                                        </span>
                                    )}
                                    {classe === 'a-venir' && (
                                        <span
                                            className="muted"
                                            style={{
                                                fontSize: 13,
                                                fontWeight: 600,
                                            }}
                                        >
                                            Débute dans{' '}
                                            {inscription.semainesAvantDebut}{' '}
                                            semaine
                                            {(inscription.semainesAvantDebut ??
                                                0) > 1
                                                ? 's'
                                                : ''}
                                        </span>
                                    )}
                                    {classe === 'en-cours' &&
                                        inscription.statut === 'VALIDEE' && (
                                            <Link
                                                href={espaceApprenant.progression.show(
                                                    inscription.id,
                                                )}
                                                className="btn-outline"
                                            >
                                                Voir ma progression
                                            </Link>
                                        )}
                                </div>

                                {classe === 'en-cours' &&
                                    inscription.statut === 'VALIDEE' && (
                                        <div style={{ marginTop: 16 }}>
                                            <div className="espace-formule-progress">
                                                <div
                                                    className="espace-formule-progress-bar"
                                                    style={{
                                                        width: `${inscription.progression}%`,
                                                    }}
                                                />
                                            </div>
                                            <p
                                                className="muted"
                                                style={{
                                                    marginTop: 6,
                                                    fontSize: 12,
                                                    textAlign: 'right',
                                                }}
                                            >
                                                {inscription.progression}%
                                            </p>
                                        </div>
                                    )}
                                {classe === 'terminees' && (
                                    <div
                                        className="espace-formule-progress"
                                        style={{ marginTop: 16 }}
                                    >
                                        <div
                                            className="espace-formule-progress-bar"
                                            style={{ width: '100%' }}
                                        />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </>
    );
}
