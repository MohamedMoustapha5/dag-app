import { Head, Link, router } from '@inertiajs/react';
import candidatures from '@/routes/espace-client/candidatures';
import offres from '@/routes/espace-client/offres';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Props = {
    candidature: {
        id: string;
        statut: string;
        offreId: string;
        offreTitre: string;
    };
    apprenant: {
        nom: string;
        email: string;
        telephone: string | null;
        ville: string | null;
    };
    formations: { nom: string; statut: string }[];
    cv: {
        disponible: boolean;
        nomFichier: string | null;
    };
};

export default function CandidaturesShow({
    candidature,
    apprenant,
    formations,
    cv,
}: Props) {
    const decider = (statut: 'RETENUE' | 'REFUSEE') => {
        router.patch(
            candidatures.update(candidature.id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    const decisionAttendue =
        candidature.statut === 'EN_ETUDE' || candidature.statut === 'DEPOSEE';

    return (
        <>
            <Head title={`Dossier de ${apprenant.nom}`} />

            <Link
                href={candidatures.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes les candidatures
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
                        {apprenant.nom}
                    </h1>
                    <Link
                        href={offres.show(candidature.offreId)}
                        className="muted"
                        style={{ fontSize: 13 }}
                    >
                        Candidature pour « {candidature.offreTitre} »
                    </Link>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[candidature.statut] ?? candidature.statut}
                </span>
            </div>

            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))',
                    gap: 20,
                    marginBottom: 20,
                }}
            >
                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Coordonnées
                    </p>
                    <dl style={{ display: 'grid', gap: 10, fontSize: 13.5 }}>
                        <div>
                            <dt className="muted" style={{ fontSize: 12 }}>
                                Email
                            </dt>
                            <dd>{apprenant.email}</dd>
                        </div>
                        <div>
                            <dt className="muted" style={{ fontSize: 12 }}>
                                Téléphone
                            </dt>
                            <dd>{apprenant.telephone ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="muted" style={{ fontSize: 12 }}>
                                Ville
                            </dt>
                            <dd>{apprenant.ville ?? '—'}</dd>
                        </div>
                    </dl>
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        CV
                    </p>
                    {cv.disponible ? (
                        <>
                            <p
                                className="muted"
                                style={{ fontSize: 13, marginBottom: 12 }}
                            >
                                {cv.nomFichier}
                            </p>
                            <a
                                href={candidatures.cv(candidature.id).url}
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
                            Le candidat n'a pas autorisé le partage de son CV
                            avec les entreprises partenaires.
                        </p>
                    )}
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Parcours de formation DAG
                </p>
                {formations.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucune formation suivie pour le moment.
                    </p>
                ) : (
                    <ul
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 10,
                        }}
                    >
                        {formations.map((formation, index) => (
                            <li
                                key={`${formation.nom}-${index}`}
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                    fontSize: 13.5,
                                }}
                            >
                                <span>{formation.nom}</span>
                                <span
                                    className={`chip ${STATUT_CHIP[formation.statut] ?? 'chip-gray'}`}
                                >
                                    {STATUT_LABEL[formation.statut] ??
                                        formation.statut}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {decisionAttendue && (
                <div style={{ display: 'flex', gap: 12 }}>
                    <button
                        type="button"
                        className="btn-outline"
                        style={{ color: 'var(--dag-red)' }}
                        onClick={() => decider('REFUSEE')}
                    >
                        Refuser
                    </button>
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => decider('RETENUE')}
                    >
                        Sélectionner ce candidat
                    </button>
                </div>
            )}
        </>
    );
}
