import { Head, Link, useForm } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { useRef } from 'react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import espaceApprenant from '@/routes/espace-apprenant';
import attestationsRoutes from '@/routes/espace-apprenant/attestations';
import dossierRoutes from '@/routes/espace-apprenant/dossier';

type FormationSuivie = {
    inscriptionId: string;
    nom: string;
    ville: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    statut: string;
    progression: number;
    seancesTotal: number;
    seancesRestantes: number;
    attestation: {
        id: string | null;
        validee: boolean;
        dateEmission: string | null;
    };
};

type Props = {
    apprenant: EspaceApprenantInfo;
    dossier: {
        ville: string | null;
        telephone: string | null;
        email: string;
        cvNomFichier: string | null;
        consentementCv: boolean;
    };
    formations: FormationSuivie[];
};

export default function ApprenantDossier({
    apprenant,
    dossier,
    formations,
}: Props) {
    const cvInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, patch, processing } = useForm({
        cv: null as File | null,
        consentement_cv: dossier.consentementCv,
    });

    const submitCv = (file: File) => {
        setData('cv', file);
        patch(dossierRoutes.update().url, {
            onSuccess: () => setData('cv', null),
        });
    };

    const toggleConsentement = (checked: boolean) => {
        setData('consentement_cv', checked);
        patch(dossierRoutes.update().url);
    };

    const initiales = apprenant.nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');

    return (
        <>
            <Head title="Mon dossier" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/man-laptop-smile.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Mon dossier</h1>
                    <p>Profil, formation suivie et présence.</p>
                </div>
            </div>

            <div className="espace-dossier-grid">
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    <div
                        className="card"
                        style={{ padding: 28, textAlign: 'center' }}
                    >
                        <span
                            className="espace-avatar"
                            style={{
                                width: 72,
                                height: 72,
                                fontSize: 22,
                                margin: '0 auto',
                            }}
                        >
                            {initiales}
                        </span>
                        <h2
                            style={{
                                marginTop: 14,
                                fontSize: 17,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            {apprenant.nom}
                        </h2>
                        <p className="muted" style={{ fontSize: 13 }}>
                            {dossier.ville ?? 'Ville non renseignée'}
                        </p>

                        <div
                            style={{
                                marginTop: 20,
                                paddingTop: 20,
                                borderTop: '1px solid var(--dag-border)',
                                textAlign: 'left',
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 10,
                            }}
                        >
                            <div>
                                <span
                                    className="muted"
                                    style={{ fontSize: 11.5 }}
                                >
                                    E-mail
                                </span>
                                <p style={{ fontSize: 13.5, fontWeight: 600 }}>
                                    {dossier.email}
                                </p>
                            </div>
                            <div>
                                <span
                                    className="muted"
                                    style={{ fontSize: 11.5 }}
                                >
                                    Téléphone
                                </span>
                                <p style={{ fontSize: 13.5, fontWeight: 600 }}>
                                    {dossier.telephone ?? '—'}
                                </p>
                            </div>
                        </div>

                        <div
                            style={{
                                marginTop: 18,
                                padding: '12px 14px',
                                borderRadius: 'var(--dag-radius-sm)',
                                border: '1px solid var(--dag-border)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                gap: 10,
                                textAlign: 'left',
                            }}
                        >
                            <span
                                style={{
                                    flex: 1,
                                    minWidth: 0,
                                    fontSize: 12.5,
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                📄 {dossier.cvNomFichier ?? 'Aucun CV importé'}
                            </span>
                            <button
                                type="button"
                                className="link-action"
                                style={{ flexShrink: 0 }}
                                disabled={processing}
                                onClick={() => cvInputRef.current?.click()}
                            >
                                {dossier.cvNomFichier
                                    ? 'Remplacer'
                                    : 'Importer'}
                            </button>
                            <input
                                ref={cvInputRef}
                                type="file"
                                accept=".pdf,.doc,.docx"
                                style={{ display: 'none' }}
                                onChange={(event) => {
                                    const file = event.target.files?.[0];
                                    if (file) {
                                        submitCv(file);
                                    }
                                }}
                            />
                        </div>
                        {data.cv && (
                            <p
                                className="muted"
                                style={{ fontSize: 11.5, marginTop: 6 }}
                            >
                                {processing ? 'Envoi en cours…' : data.cv.name}
                            </p>
                        )}

                        <label
                            style={{
                                marginTop: 14,
                                display: 'flex',
                                alignItems: 'flex-start',
                                gap: 8,
                                fontSize: 11.5,
                                lineHeight: 1.5,
                                textAlign: 'left',
                                color: 'var(--dag-text-muted)',
                                cursor: 'pointer',
                            }}
                        >
                            <input
                                type="checkbox"
                                checked={data.consentement_cv}
                                onChange={(event) =>
                                    toggleConsentement(event.target.checked)
                                }
                                style={{ marginTop: 2 }}
                            />
                            J'autorise DAG à transmettre mon CV aux entreprises
                            partenaires.
                        </label>
                    </div>
                </div>

                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 15,
                                fontWeight: 700,
                                marginBottom: 16,
                            }}
                        >
                            Mes formations
                        </p>
                        {formations.length === 0 ? (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune formation en cours pour le moment.
                            </p>
                        ) : (
                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 16,
                                }}
                            >
                                {formations.map((formation) => (
                                    <div
                                        key={formation.inscriptionId}
                                        style={{
                                            padding: 16,
                                            borderRadius:
                                                'var(--dag-radius-sm)',
                                            border: '1px solid var(--dag-border)',
                                        }}
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
                                                        fontSize: 14.5,
                                                        color: 'var(--dag-navy)',
                                                    }}
                                                >
                                                    {formation.nom}
                                                </h3>
                                                <span
                                                    className={`chip ${STATUT_CHIP[formation.statut] ?? 'chip-gray'}`}
                                                >
                                                    {STATUT_LABEL[
                                                        formation.statut
                                                    ] ?? formation.statut}
                                                </span>
                                            </div>
                                            <Link
                                                href={espaceApprenant.progression.show(
                                                    formation.inscriptionId,
                                                )}
                                                className="btn-outline"
                                                style={{
                                                    padding: '6px 14px',
                                                    fontSize: 12.5,
                                                }}
                                            >
                                                Suivre ma présence
                                            </Link>
                                        </div>
                                        <p
                                            className="muted"
                                            style={{
                                                marginTop: 6,
                                                fontSize: 12,
                                            }}
                                        >
                                            Session du {formation.dateDebut}
                                            {formation.dateFin
                                                ? ` au ${formation.dateFin}`
                                                : ''}
                                            {formation.ville
                                                ? ` · ${formation.ville}`
                                                : ''}
                                        </p>
                                        <div style={{ marginTop: 12 }}>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    justifyContent:
                                                        'space-between',
                                                    fontSize: 11.5,
                                                }}
                                            >
                                                <span className="muted">
                                                    {formation.seancesTotal}{' '}
                                                    séance(s) au total
                                                </span>
                                                <strong>
                                                    {formation.progression}%
                                                </strong>
                                            </div>
                                            <div className="espace-formule-progress">
                                                <div
                                                    className="espace-formule-progress-bar"
                                                    style={{
                                                        width: `${formation.progression}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>

                                        <div
                                            style={{
                                                marginTop: 14,
                                                paddingTop: 14,
                                                borderTop:
                                                    '1px solid var(--dag-border)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'space-between',
                                                gap: 12,
                                                flexWrap: 'wrap',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: 8,
                                                }}
                                            >
                                                <Award
                                                    size={14}
                                                    style={{
                                                        color: 'var(--dag-text-muted)',
                                                    }}
                                                />
                                                <span
                                                    className="muted"
                                                    style={{ fontSize: 11.5 }}
                                                >
                                                    {formation.attestation
                                                        .validee
                                                        ? `Attestation validée le ${formation.attestation.dateEmission}`
                                                        : formation.seancesRestantes ===
                                                            0
                                                          ? "Formation terminée — en attente de validation par l'équipe DAG"
                                                          : 'Attestation disponible une fois la formation terminée et validée par DAG'}
                                                </span>
                                            </div>
                                            {formation.attestation.validee ? (
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 10,
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <span className="chip chip-green">
                                                        Validée
                                                    </span>
                                                    {formation.attestation
                                                        .id && (
                                                        <a
                                                            href={
                                                                attestationsRoutes.pdf(
                                                                    formation
                                                                        .attestation
                                                                        .id,
                                                                ).url
                                                            }
                                                            className="link-action"
                                                        >
                                                            Télécharger
                                                        </a>
                                                    )}
                                                </div>
                                            ) : formation.seancesRestantes ===
                                              0 ? (
                                                <span className="chip chip-orange">
                                                    En attente de validation
                                                </span>
                                            ) : (
                                                <span className="chip chip-gray">
                                                    En cours
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
