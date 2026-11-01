import { Head, Link, useForm } from '@inertiajs/react';
import { Award, Check, X } from 'lucide-react';
import { useRef } from 'react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import { offresEmploi } from '@/routes';
import dossierRoutes from '@/routes/espace-apprenant/dossier';

type FormationSuivie = {
    nom: string;
    ville: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    seancesCompletees: number;
    seancesTotal: number;
};

type Candidature = {
    id: string;
    titre: string;
    entreprise: string;
    typeContrat: string;
    duree: string | null;
    dateDepot: string | null;
    statut: string;
};

type Presence = {
    id: string;
    titre: string;
    date: string | null;
    present: boolean | null;
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
    formationSuivie: FormationSuivie | null;
    attestation: {
        seancesRestantes: number;
    };
    candidatures: Candidature[];
    presences: Presence[];
};

export default function ApprenantDossier({
    apprenant,
    dossier,
    formationSuivie,
    attestation,
    candidatures,
    presences,
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
                    <p>Profil, formation suivie et candidatures.</p>
                </div>
            </div>

            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: '320px 1fr',
                    gap: 24,
                    alignItems: 'start',
                }}
            >
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
                            {dossier.ville ?? 'Ville non renseignée'}, Cameroun
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

                    {formationSuivie && (
                        <div className="card" style={{ padding: 24 }}>
                            <span className="badge">Formation suivie</span>
                            <h3
                                style={{
                                    marginTop: 10,
                                    fontSize: 15,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {formationSuivie.nom}
                            </h3>
                            <p
                                className="muted"
                                style={{ marginTop: 4, fontSize: 12 }}
                            >
                                Session du {formationSuivie.dateDebut}
                                {formationSuivie.dateFin
                                    ? ` au ${formationSuivie.dateFin}`
                                    : ''}
                                {formationSuivie.ville
                                    ? ` · ${formationSuivie.ville}`
                                    : ''}
                            </p>
                            <div style={{ marginTop: 16 }}>
                                <div
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        fontSize: 12,
                                    }}
                                >
                                    <span className="muted">Progression</span>
                                    <strong>
                                        {formationSuivie.seancesTotal > 0
                                            ? Math.round(
                                                  (formationSuivie.seancesCompletees /
                                                      formationSuivie.seancesTotal) *
                                                      100,
                                              )
                                            : 0}
                                        %
                                    </strong>
                                </div>
                                <div className="espace-formule-progress">
                                    <div
                                        className="espace-formule-progress-bar"
                                        style={{
                                            width: `${formationSuivie.seancesTotal > 0 ? (formationSuivie.seancesCompletees / formationSuivie.seancesTotal) * 100 : 0}%`,
                                        }}
                                    />
                                </div>
                                <p
                                    className="muted"
                                    style={{ marginTop: 8, fontSize: 12 }}
                                >
                                    {formationSuivie.seancesCompletees} séances
                                    sur {formationSuivie.seancesTotal}{' '}
                                    complétées
                                </p>
                            </div>
                        </div>
                    )}
                </div>

                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    <div
                        className="card"
                        style={{
                            padding: 24,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            gap: 16,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 12,
                            }}
                        >
                            <span
                                className="espace-stat-icon"
                                style={{
                                    background: 'var(--dag-blue-light)',
                                    color: 'var(--dag-blue)',
                                    margin: 0,
                                }}
                            >
                                <Award size={18} />
                            </span>
                            <div>
                                <span
                                    className="muted"
                                    style={{
                                        fontSize: 11,
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.04em',
                                        fontWeight: 700,
                                    }}
                                >
                                    Attestation de formation
                                </span>
                                <p
                                    className="muted"
                                    style={{ fontSize: 13, marginTop: 4 }}
                                >
                                    Générée automatiquement en PDF à la fin de
                                    la session — {attestation.seancesRestantes}{' '}
                                    séance(s) restante(s).
                                </p>
                            </div>
                        </div>
                        <span
                            className="btn-outline"
                            style={{
                                opacity: 0.5,
                                cursor: 'not-allowed',
                                flexShrink: 0,
                            }}
                            title="Disponible une fois la formation terminée"
                        >
                            Aperçu de l'attestation
                        </span>
                    </div>

                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 15,
                                fontWeight: 700,
                                marginBottom: 16,
                            }}
                        >
                            Mes candidatures
                        </p>
                        {candidatures.length === 0 ? (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune candidature envoyée pour le moment.
                            </p>
                        ) : (
                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 12,
                                }}
                            >
                                {candidatures.map((candidature) => (
                                    <div
                                        key={candidature.id}
                                        style={{
                                            padding: '14px 16px',
                                            borderRadius:
                                                'var(--dag-radius-sm)',
                                            border: '1px solid var(--dag-border)',
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            gap: 12,
                                            flexWrap: 'wrap',
                                        }}
                                    >
                                        <div>
                                            <p
                                                style={{
                                                    fontSize: 14,
                                                    fontWeight: 700,
                                                    color: 'var(--dag-navy)',
                                                }}
                                            >
                                                {candidature.titre} —{' '}
                                                {candidature.entreprise}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {candidature.typeContrat}
                                                {candidature.duree
                                                    ? ` ${candidature.duree}`
                                                    : ''}{' '}
                                                · déposée le{' '}
                                                {candidature.dateDepot}
                                            </p>
                                        </div>
                                        <span
                                            className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[candidature.statut] ??
                                                candidature.statut}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                        <Link
                            href={offresEmploi()}
                            className="link-action"
                            style={{ marginTop: 16, display: 'inline-block' }}
                        >
                            Voir toutes les offres disponibles →
                        </Link>
                    </div>

                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 15,
                                fontWeight: 700,
                                marginBottom: 16,
                            }}
                        >
                            Présence
                        </p>
                        {presences.length === 0 ? (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune séance programmée pour le moment.
                            </p>
                        ) : (
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Séance</th>
                                            <th>Date</th>
                                            <th>Présence</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {presences.map((presence) => (
                                            <tr key={presence.id}>
                                                <td
                                                    style={{
                                                        fontWeight: 600,
                                                    }}
                                                >
                                                    {presence.titre}
                                                </td>
                                                <td className="muted">
                                                    {presence.date}
                                                </td>
                                                <td>
                                                    {presence.present ===
                                                    true ? (
                                                        <span className="chip chip-green">
                                                            <Check
                                                                size={11}
                                                                style={{
                                                                    marginRight: 4,
                                                                    verticalAlign:
                                                                        -1,
                                                                }}
                                                            />
                                                            Présente
                                                        </span>
                                                    ) : presence.present ===
                                                      false ? (
                                                        <span className="chip chip-red">
                                                            <X
                                                                size={11}
                                                                style={{
                                                                    marginRight: 4,
                                                                    verticalAlign:
                                                                        -1,
                                                                }}
                                                            />
                                                            Absente
                                                        </span>
                                                    ) : (
                                                        <span className="chip chip-gray">
                                                            À venir
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
                </div>
            </div>
        </>
    );
}
