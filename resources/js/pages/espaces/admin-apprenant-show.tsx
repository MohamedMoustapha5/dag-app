import { Head, Link } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';

type Props = {
    apprenant: {
        id: string;
        nom: string;
        email: string;
        telephone: string | null;
        ville: string | null;
        cvNomFichier: string | null;
        consentementCv: boolean;
    };
    inscriptions: { id: string; formation: string; statut: string }[];
    candidatures: {
        id: string;
        offreTitre: string;
        entrepriseNom: string;
        statut: string;
    }[];
    attestations: { id: string; reference: string; formation: string }[];
};

export default function AdminApprenantShow({
    apprenant,
    inscriptions,
    candidatures,
    attestations,
}: Props) {
    return (
        <>
            <Head title={`${apprenant.nom} — Administration`} />

            <Link
                href={apprenantsAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Tous les apprenants
            </Link>

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 24,
                }}
            >
                {apprenant.nom}
            </h1>

            <div
                className="grid-3 espace-stats-grid"
                style={{ marginBottom: 28 }}
            >
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        E-mail
                    </p>
                    <p style={{ fontWeight: 600 }}>{apprenant.email}</p>
                </div>
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Téléphone
                    </p>
                    <p style={{ fontWeight: 600 }}>
                        {apprenant.telephone ?? '—'}
                    </p>
                </div>
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Ville
                    </p>
                    <p style={{ fontWeight: 600 }}>{apprenant.ville ?? '—'}</p>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    CV
                </p>
                {apprenant.cvNomFichier ? (
                    <>
                        <p
                            className="muted"
                            style={{ fontSize: 13, marginBottom: 12 }}
                        >
                            {apprenant.cvNomFichier}
                            {!apprenant.consentementCv &&
                                " · partage avec les entreprises non autorisé par l'apprenant"}
                        </p>
                        <a
                            href={apprenantsAdmin.cv(apprenant.id).url}
                            className="btn-outline"
                            style={{
                                display: 'inline-block',
                                padding: '8px 16px',
                                fontSize: 13,
                            }}
                        >
                            Télécharger le CV
                        </a>
                    </>
                ) : (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun CV importé par l'apprenant.
                    </p>
                )}
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Formations suivies ({inscriptions.length})
                </p>
                {inscriptions.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucune inscription pour le moment.
                    </p>
                ) : (
                    <ul
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 10,
                        }}
                    >
                        {inscriptions.map((inscription) => (
                            <li
                                key={inscription.id}
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                    fontSize: 13.5,
                                }}
                            >
                                <span>{inscription.formation}</span>
                                <span
                                    className={`chip ${STATUT_CHIP[inscription.statut] ?? 'chip-gray'}`}
                                >
                                    {STATUT_LABEL[inscription.statut] ??
                                        inscription.statut}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Candidatures ({candidatures.length})
                </p>
                {candidatures.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucune candidature pour le moment.
                    </p>
                ) : (
                    <ul
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 10,
                        }}
                    >
                        {candidatures.map((candidature) => (
                            <li
                                key={candidature.id}
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                    fontSize: 13.5,
                                }}
                            >
                                <span>
                                    {candidature.offreTitre} —{' '}
                                    <span className="muted">
                                        {candidature.entrepriseNom}
                                    </span>
                                </span>
                                <span
                                    className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                                >
                                    {STATUT_LABEL[candidature.statut] ??
                                        candidature.statut}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Attestations ({attestations.length})
                </p>
                {attestations.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucune attestation validée pour le moment.
                    </p>
                ) : (
                    <ul
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 10,
                        }}
                    >
                        {attestations.map((attestation) => (
                            <li key={attestation.id} style={{ fontSize: 13.5 }}>
                                {attestation.formation} —{' '}
                                <span className="muted">
                                    {attestation.reference}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
