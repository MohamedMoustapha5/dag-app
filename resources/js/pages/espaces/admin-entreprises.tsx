import { Head, router, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import entreprisesAdmin from '@/routes/espace-admin/entreprises';
import offresAdmin from '@/routes/espace-admin/offres';

type Entreprise = {
    id: string;
    nom: string;
    secteur: string | null;
    email: string | null;
    telephone: string | null;
    statut: string;
    offresCount: number;
    auCompteClient: boolean;
};

type Offre = {
    id: string;
    titre: string;
    entrepriseNom: string;
    typeContrat: string;
    ville: string | null;
    statut: string;
    candidaturesCount: number;
    postulable: boolean;
    dateLimite: string | null;
    expiree: boolean;
    apprenantIdsCandidats: string[];
};

type ApprenantPostulable = {
    id: string;
    source: 'apprenant' | 'externe';
    externe: boolean;
    nom: string;
    ville: string | null;
    estForme: boolean;
    formations: string[];
    aUnCv: boolean;
    consentement: boolean;
};

type Props = {
    entreprises: Entreprise[];
    offres: Offre[];
    peutValider: boolean;
    peutModifier: boolean;
    peutPostuler: boolean;
    apprenants: ApprenantPostulable[];
};

export default function AdminEntreprises({
    entreprises,
    offres,
    peutValider,
    peutModifier,
    peutPostuler,
    apprenants,
}: Props) {
    const [offrePourPostuler, setOffrePourPostuler] = useState<Offre | null>(
        null,
    );
    const [offrePourProlonger, setOffrePourProlonger] =
        useState<Offre | null>(null);

    const changerStatutEntreprise = (id: string, statut: string) => {
        router.patch(
            entreprisesAdmin.update(id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    const changerStatutOffre = (id: string, statut: string) => {
        router.patch(
            offresAdmin.update(id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Entreprises & offres — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/handshake-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Entreprises & offres</h1>
                    <p>
                        Validez les entreprises partenaires et publiez leurs
                        offres d'emploi — rien n'est jamais mis en ligne
                        automatiquement.
                    </p>
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
                    Entreprises partenaires ({entreprises.length})
                </p>
                {entreprises.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune entreprise partenaire pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Entreprise</th>
                                    <th>Contact</th>
                                    <th>Offres</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {entreprises.map((entreprise) => (
                                    <tr key={entreprise.id}>
                                        <td>
                                            <p style={{ fontWeight: 600 }}>
                                                {entreprise.nom}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {entreprise.secteur ?? '—'}
                                                {entreprise.auCompteClient &&
                                                    ' · compte client lié'}
                                            </p>
                                        </td>
                                        <td className="muted">
                                            {entreprise.email ?? '—'}
                                            {entreprise.telephone
                                                ? ` · ${entreprise.telephone}`
                                                : ''}
                                        </td>
                                        <td className="muted">
                                            {entreprise.offresCount}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[entreprise.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[
                                                    entreprise.statut
                                                ] ?? entreprise.statut}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {entreprise.statut ===
                                                'EN_ATTENTE' &&
                                                peutValider && (
                                                    <button
                                                        type="button"
                                                        className="btn-primary"
                                                        style={{
                                                            padding: '6px 14px',
                                                            fontSize: 12.5,
                                                        }}
                                                        onClick={() =>
                                                            changerStatutEntreprise(
                                                                entreprise.id,
                                                                'ACTIF',
                                                            )
                                                        }
                                                    >
                                                        Valider le partenariat
                                                    </button>
                                                )}
                                            {entreprise.statut === 'ACTIF' &&
                                                peutModifier && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        style={{
                                                            color: 'var(--dag-red)',
                                                        }}
                                                        onClick={() =>
                                                            changerStatutEntreprise(
                                                                entreprise.id,
                                                                'INACTIF',
                                                            )
                                                        }
                                                    >
                                                        Désactiver
                                                    </button>
                                                )}
                                            {entreprise.statut === 'INACTIF' &&
                                                peutValider && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        onClick={() =>
                                                            changerStatutEntreprise(
                                                                entreprise.id,
                                                                'ACTIF',
                                                            )
                                                        }
                                                    >
                                                        Réactiver
                                                    </button>
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
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Offres d'emploi ({offres.length})
                </p>
                {offres.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune offre d'emploi pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Offre</th>
                                    <th>Entreprise</th>
                                    <th>Type</th>
                                    <th>Candidatures</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {offres.map((offre) => (
                                    <tr key={offre.id}>
                                        <td>
                                            <p style={{ fontWeight: 600 }}>
                                                {offre.titre}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {offre.ville ?? '—'}
                                                {offre.dateLimite &&
                                                    ` · limite ${offre.dateLimite}`}
                                            </p>
                                        </td>
                                        <td className="muted">
                                            {offre.entrepriseNom}
                                        </td>
                                        <td className="muted">
                                            {offre.typeContrat}
                                        </td>
                                        <td className="muted">
                                            {offre.candidaturesCount}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${offre.expiree ? 'chip-gray' : offre.statut === 'PUBLIEE' ? 'chip-green' : (STATUT_CHIP[offre.statut] ?? 'chip-gray')}`}
                                            >
                                                {offre.expiree
                                                    ? 'Expirée'
                                                    : offre.statut === 'PUBLIEE'
                                                    ? 'Publiée'
                                                    : offre.statut ===
                                                        'BROUILLON'
                                                      ? 'En attente de publication'
                                                      : (STATUT_LABEL[
                                                            offre.statut
                                                        ] ?? offre.statut)}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {peutPostuler && (
                                                <button
                                                    type="button"
                                                    className={
                                                        offre.postulable
                                                            ? 'btn-primary'
                                                            : 'btn-outline'
                                                    }
                                                    style={{
                                                        padding: '6px 14px',
                                                        fontSize: 12.5,
                                                        marginRight: 12,
                                                        opacity:
                                                            offre.postulable
                                                                ? 1
                                                                : 0.55,
                                                        cursor: offre.postulable
                                                            ? 'pointer'
                                                            : 'not-allowed',
                                                    }}
                                                    disabled={!offre.postulable}
                                                    title={
                                                        offre.postulable
                                                            ? undefined
                                                            : offre.expiree
                                                              ? 'Date limite dépassée : prolongez l\'offre pour postuler'
                                                              : 'Seule une offre publiée accepte des candidatures'
                                                    }
                                                    onClick={() =>
                                                        setOffrePourPostuler(
                                                            offre,
                                                        )
                                                    }
                                                >
                                                    Postuler
                                                </button>
                                            )}
                                            {offre.expiree && peutValider && (
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    style={{ marginRight: 12 }}
                                                    onClick={() =>
                                                        setOffrePourProlonger(
                                                            offre,
                                                        )
                                                    }
                                                >
                                                    Prolonger
                                                </button>
                                            )}
                                            {offre.statut === 'BROUILLON' &&
                                                peutValider && (
                                                    <button
                                                        type="button"
                                                        className="btn-primary"
                                                        style={{
                                                            padding: '6px 14px',
                                                            fontSize: 12.5,
                                                        }}
                                                        onClick={() =>
                                                            changerStatutOffre(
                                                                offre.id,
                                                                'PUBLIEE',
                                                            )
                                                        }
                                                    >
                                                        Publier
                                                    </button>
                                                )}
                                            {offre.statut === 'PUBLIEE' &&
                                                peutModifier && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        onClick={() =>
                                                            changerStatutOffre(
                                                                offre.id,
                                                                'CLOTUREE',
                                                            )
                                                        }
                                                    >
                                                        Clôturer
                                                    </button>
                                                )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {offrePourProlonger && (
                <ProlongerModal
                    offre={offrePourProlonger}
                    onClose={() => setOffrePourProlonger(null)}
                />
            )}

            {offrePourPostuler && (
                <PostulerModal
                    offre={offrePourPostuler}
                    apprenants={apprenants}
                    onClose={() => setOffrePourPostuler(null)}
                />
            )}
        </>
    );
}

function ProlongerModal({
    offre,
    onClose,
}: {
    offre: Offre;
    onClose: () => void;
}) {
    const form = useForm({ statut: 'PUBLIEE', date_limite: '' });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.patch(offresAdmin.update(offre.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Prolonger l'offre"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>
                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Prolonger l'offre
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 20,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    « {offre.titre} » — {offre.entrepriseNom}. La date limite
                    ({offre.dateLimite}) est dépassée : choisissez-en une
                    nouvelle pour rouvrir les candidatures.
                </p>
                <form
                    onSubmit={submit}
                    style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
                >
                    <div>
                        <label htmlFor="date-limite">Nouvelle date limite</label>
                        <input
                            id="date-limite"
                            type="date"
                            value={form.data.date_limite}
                            min={new Date().toISOString().slice(0, 10)}
                            onChange={(event) =>
                                form.setData('date_limite', event.target.value)
                            }
                            required
                        />
                        {form.errors.date_limite && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.date_limite}
                            </p>
                        )}
                    </div>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            gap: 12,
                        }}
                    >
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={onClose}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            Prolonger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function PostulerModal({
    offre,
    apprenants,
    onClose,
}: {
    offre: Offre;
    apprenants: ApprenantPostulable[];
    onClose: () => void;
}) {
    const [recherche, setRecherche] = useState('');
    const [filtre, setFiltre] = useState<'tous' | 'formes' | 'externes'>(
        'tous',
    );
    const form = useForm({ apprenant_id: '', candidature_spontanee_id: '' });

    const liste = useMemo(() => {
        const terme = recherche.trim().toLowerCase();

        return apprenants.filter(
            (apprenant) =>
                (filtre === 'tous' ||
                    (filtre === 'formes' && apprenant.estForme) ||
                    (filtre === 'externes' && apprenant.externe)) &&
                (terme === '' ||
                    apprenant.nom.toLowerCase().includes(terme) ||
                    (apprenant.ville ?? '').toLowerCase().includes(terme)),
        );
    }, [apprenants, recherche, filtre]);

    const raisonBlocage = (apprenant: ApprenantPostulable): string | null => {
        if (offre.apprenantIdsCandidats.includes(apprenant.id)) {
            return 'A déjà postulé';
        }

        if (!apprenant.consentement) {
            return 'Partage du dossier non autorisé';
        }

        return null;
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(offresAdmin.postuler(offre.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Postuler pour un apprenant"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Postuler pour un apprenant
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 20,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    {offre.titre} — {offre.entrepriseNom}. Choisissez un dossier
                    parmi tous les candidats : apprenants DAG, anciens apprenants et dossiers reçus de candidats externes.
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 14,
                    }}
                >
                    <div style={{ display: 'flex', gap: 10 }}>
                        <input
                            type="search"
                            placeholder="Rechercher un nom ou une ville"
                            value={recherche}
                            onChange={(event) =>
                                setRecherche(event.target.value)
                            }
                            style={{ flex: 1 }}
                        />
                        <select
                            aria-label="Filtrer les apprenants"
                            value={filtre}
                            onChange={(event) =>
                                setFiltre(
                                    event.target.value as
                                        | 'tous'
                                        | 'formes'
                                        | 'externes',
                                )
                            }
                        >
                            <option value="tous">Tous</option>
                            <option value="formes">Formés par DAG</option>
                            <option value="externes">Dossiers externes</option>
                        </select>
                    </div>

                    <div
                        style={{
                            maxHeight: 280,
                            overflowY: 'auto',
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 8,
                        }}
                    >
                        {liste.length === 0 && (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucun apprenant ne correspond.
                            </p>
                        )}
                        {liste.map((apprenant) => {
                            const blocage = raisonBlocage(apprenant);

                            return (
                                <label
                                    key={`${apprenant.source}-${apprenant.id}`}
                                    style={{
                                        display: 'flex',
                                        gap: 10,
                                        alignItems: 'flex-start',
                                        padding: '10px 12px',
                                        border: '1px solid var(--dag-border)',
                                        borderRadius: 10,
                                        opacity: blocage ? 0.55 : 1,
                                        cursor: blocage
                                            ? 'not-allowed'
                                            : 'pointer',
                                    }}
                                >
                                    <input
                                        type="radio"
                                        name="apprenant_id"
                                        value={apprenant.id}
                                        disabled={blocage !== null}
                                        checked={
                                            (apprenant.source === 'apprenant'
                                                ? form.data.apprenant_id
                                                : form.data
                                                      .candidature_spontanee_id) ===
                                            apprenant.id
                                        }
                                        onChange={() =>
                                            form.setData(
                                                apprenant.source === 'apprenant'
                                                    ? {
                                                          apprenant_id:
                                                              apprenant.id,
                                                          candidature_spontanee_id:
                                                              '',
                                                      }
                                                    : {
                                                          apprenant_id: '',
                                                          candidature_spontanee_id:
                                                              apprenant.id,
                                                      },
                                            )
                                        }
                                    />
                                    <span style={{ flex: 1, fontSize: 13.5 }}>
                                        <strong>{apprenant.nom}</strong>
                                        <span
                                            className="muted"
                                            style={{ display: 'block' }}
                                        >
                                            {apprenant.ville ?? '—'}
                                            {apprenant.formations.length > 0 &&
                                                ` · ${apprenant.formations.join(', ')}`}
                                            {!apprenant.aUnCv && ' · sans CV'}
                                        </span>
                                        {blocage && (
                                            <span
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                }}
                                            >
                                                {blocage}
                                            </span>
                                        )}
                                    </span>
                                    {apprenant.externe ? (
                                        <span className="chip chip-gray">
                                            Dossier externe
                                        </span>
                                    ) : (
                                        apprenant.estForme && (
                                            <span className="chip chip-green">
                                                Formé
                                            </span>
                                        )
                                    )}
                                </label>
                            );
                        })}
                    </div>

                    {(form.errors.apprenant_id ||
                        form.errors.candidature_spontanee_id) && (
                        <p style={{ color: 'var(--dag-red)', fontSize: 12.5 }}>
                            {form.errors.apprenant_id ??
                                form.errors.candidature_spontanee_id}
                        </p>
                    )}

                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            gap: 12,
                        }}
                    >
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={onClose}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={
                                form.processing ||
                                (form.data.apprenant_id === '' &&
                                    form.data.candidature_spontanee_id === '')
                            }
                        >
                            Déposer la candidature
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
