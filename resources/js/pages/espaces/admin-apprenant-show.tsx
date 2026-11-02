import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';
import attestationsAdmin from '@/routes/espace-admin/attestations';
import offresAdmin from '@/routes/espace-admin/offres';

type Props = {
    apprenant: {
        id: string;
        nom: string;
        email: string;
        telephone: string | null;
        ville: string | null;
        cvNomFichier: string | null;
        consentementCv: boolean;
        estForme: boolean;
        externe: {
            portfolio: string | null;
            message: string | null;
            recuLe: string | null;
        } | null;
    };
    peutPostuler: boolean;
    offresDisponibles: { id: string; titre: string; entrepriseNom: string }[];
    inscriptions: { id: string; formation: string; statut: string }[];
    candidatures: {
        id: string;
        offreTitre: string;
        entrepriseNom: string;
        statut: string;
        deposeeParDag: boolean;
    }[];
    attestations: { id: string; reference: string; formation: string }[];
    peutTelecharger: boolean;
};

export default function AdminApprenantShow({
    apprenant,
    inscriptions,
    candidatures,
    attestations,
    peutTelecharger,
    peutPostuler,
    offresDisponibles,
}: Props) {
    const postulerForm = useForm({ apprenant_id: apprenant.id });
    const [offreId, setOffreId] = useState('');

    const postuler = (event: React.FormEvent) => {
        event.preventDefault();
        postulerForm.post(offresAdmin.postuler(offreId).url, {
            preserveScroll: true,
            onSuccess: () => setOffreId(''),
        });
    };

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
                {apprenant.nom}{' '}
                {apprenant.estForme && (
                    <span
                        className="chip chip-green"
                        style={{ verticalAlign: 'middle' }}
                    >
                        Formé
                    </span>
                )}
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

            {apprenant.externe && (
                <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                    <p
                        style={{
                            fontSize: 14.5,
                            fontWeight: 700,
                            marginBottom: 12,
                        }}
                    >
                        Dossier reçu par DAG
                        {apprenant.externe.recuLe &&
                            ` le ${apprenant.externe.recuLe}`}
                    </p>
                    {apprenant.externe.portfolio && (
                        <p style={{ fontSize: 13.5, marginBottom: 8 }}>
                            Portfolio :{' '}
                            <a
                                href={apprenant.externe.portfolio}
                                target="_blank"
                                rel="noreferrer"
                                className="link-action"
                            >
                                {apprenant.externe.portfolio}
                            </a>
                        </p>
                    )}
                    {apprenant.externe.message && (
                        <p
                            className="muted"
                            style={{ fontSize: 13.5, lineHeight: 1.6 }}
                        >
                            {apprenant.externe.message}
                        </p>
                    )}
                </div>
            )}

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
                ) : null}
                {candidatures.length > 0 && (
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
                                    {candidature.deposeeParDag &&
                                        ' · déposée par DAG'}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
                {peutPostuler && (
                    <form
                        onSubmit={postuler}
                        style={{
                            display: 'flex',
                            gap: 10,
                            flexWrap: 'wrap',
                            marginTop: 20,
                            paddingTop: 16,
                            borderTop: '1px solid var(--dag-border)',
                        }}
                    >
                        <select
                            aria-label="Offre à laquelle postuler"
                            value={offreId}
                            onChange={(event) => setOffreId(event.target.value)}
                            style={{ flex: 1, minWidth: 220 }}
                        >
                            <option value="">
                                {offresDisponibles.length === 0
                                    ? 'Aucune offre publiée disponible'
                                    : 'Postuler à une offre…'}
                            </option>
                            {offresDisponibles.map((offre) => (
                                <option key={offre.id} value={offre.id}>
                                    {offre.titre} — {offre.entrepriseNom}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={offreId === '' || postulerForm.processing}
                        >
                            Postuler pour {apprenant.nom}
                        </button>
                        {postulerForm.errors.apprenant_id && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12.5,
                                    width: '100%',
                                }}
                            >
                                {postulerForm.errors.apprenant_id}
                            </p>
                        )}
                    </form>
                )}
                {!peutPostuler && !apprenant.consentementCv && (
                    <p className="muted" style={{ fontSize: 12.5, marginTop: 16 }}>
                        Cet apprenant n'a pas autorisé le partage de son dossier
                        avec les entreprises : DAG ne peut pas postuler à sa
                        place.
                    </p>
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
                                {peutTelecharger && (
                                    <>
                                        {' · '}
                                        <a
                                            href={
                                                attestationsAdmin.pdf(
                                                    attestation.id,
                                                ).url
                                            }
                                            className="link-action"
                                        >
                                            Télécharger le PDF
                                        </a>
                                    </>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
