import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';
import candidatsExternesAdmin from '@/routes/espace-admin/candidats-externes';
import offresAdmin from '@/routes/espace-admin/offres';

type Apprenant = {
    id: string;
    nom: string;
    email: string;
    ville: string | null;
    inscriptionsCount: number;
    estForme: boolean;
    externe: boolean;
    formations: string[];
    attestationsCount: number;
    aUnCv: boolean;
};

type DossierRecu = {
    id: string;
    nom: string;
    email: string;
    telephone: string;
    portfolio: string | null;
    message: string | null;
    statut: string;
    recuLe: string | null;
    apprenantId: string | null;
};

type Offre = { id: string; titre: string; entrepriseNom: string };

type Props = {
    apprenants: Apprenant[];
    dossiersRecus: DossierRecu[];
    offresDisponibles: Offre[];
    peutPostuler: boolean;
    peutTelecharger: boolean;
    peutSupprimer: boolean;
};

type Filtre = 'tous' | 'formes' | 'externes';

const FILTRES: { value: Filtre; label: string }[] = [
    { value: 'tous', label: 'Tous' },
    { value: 'formes', label: 'Formés par DAG' },
    { value: 'externes', label: 'Dossiers externes' },
];

export default function AdminApprenants({
    apprenants,
    dossiersRecus,
    offresDisponibles,
    peutPostuler,
    peutTelecharger,
    peutSupprimer,
}: Props) {
    const [onglet, setOnglet] = useState<'dossiers' | 'recus'>('dossiers');
    const [filtre, setFiltre] = useState<Filtre>('tous');
    const [recherche, setRecherche] = useState('');
    const [offreParDossier, setOffreParDossier] = useState<
        Record<string, string>
    >({});

    const liste = useMemo(() => {
        const terme = recherche.trim().toLowerCase();

        return apprenants.filter((apprenant) => {
            if (filtre === 'formes' && !apprenant.estForme) {
                return false;
            }

            if (filtre === 'externes' && !apprenant.externe) {
                return false;
            }

            return (
                terme === '' ||
                apprenant.nom.toLowerCase().includes(terme) ||
                apprenant.email.toLowerCase().includes(terme) ||
                (apprenant.ville ?? '').toLowerCase().includes(terme)
            );
        });
    }, [apprenants, filtre, recherche]);

    const postuler = (dossier: DossierRecu) => {
        const offreId = offreParDossier[dossier.id];

        if (!offreId) {
            return;
        }

        router.post(
            offresAdmin.postuler(offreId).url,
            { candidature_spontanee_id: dossier.id },
            { preserveScroll: true },
        );
    };

    const supprimerDossier = (dossier: DossierRecu) => {
        if (
            !confirm(
                `Supprimer le dossier de « ${dossier.nom} » ? Son CV et, s'il a été transformé en dossier apprenant, ce dossier seront supprimés définitivement.`,
            )
        ) {
            return;
        }

        router.delete(candidatsExternesAdmin.destroy(dossier.id).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Dossiers apprenants — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/lms-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Dossiers apprenants</h1>
                    <p>
                        Apprenants DAG, anciens apprenants et dossiers envoyés
                        par des candidats externes : consultez leur CV et
                        postulez à une offre à leur place.
                    </p>
                </div>
            </div>

            <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
                <button
                    type="button"
                    className={
                        onglet === 'dossiers' ? 'btn-primary' : 'btn-outline'
                    }
                    style={{ padding: '7px 16px', fontSize: 13 }}
                    onClick={() => setOnglet('dossiers')}
                >
                    Dossiers ({apprenants.length})
                </button>
                <button
                    type="button"
                    className={
                        onglet === 'recus' ? 'btn-primary' : 'btn-outline'
                    }
                    style={{ padding: '7px 16px', fontSize: 13 }}
                    onClick={() => setOnglet('recus')}
                >
                    Dossiers reçus ({dossiersRecus.length})
                </button>
            </div>

            {onglet === 'dossiers' && (
                <div className="card" style={{ padding: 24 }}>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 12,
                            flexWrap: 'wrap',
                            marginBottom: 16,
                        }}
                    >
                        <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                            Apprenants ({liste.length}/{apprenants.length})
                        </p>
                        <div
                            style={{
                                display: 'flex',
                                gap: 10,
                                flexWrap: 'wrap',
                            }}
                        >
                            {FILTRES.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    className={
                                        filtre === option.value
                                            ? 'btn-primary'
                                            : 'btn-outline'
                                    }
                                    style={{
                                        padding: '6px 14px',
                                        fontSize: 12.5,
                                    }}
                                    onClick={() => setFiltre(option.value)}
                                >
                                    {option.label}
                                </button>
                            ))}
                            <input
                                type="search"
                                placeholder="Rechercher"
                                aria-label="Rechercher un apprenant"
                                value={recherche}
                                onChange={(event) =>
                                    setRecherche(event.target.value)
                                }
                            />
                        </div>
                    </div>

                    {liste.length === 0 ? (
                        <p className="data-table-empty">
                            Aucun apprenant ne correspond.
                        </p>
                    ) : (
                        <div className="data-table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Apprenant</th>
                                        <th>Ville</th>
                                        <th>Formations</th>
                                        <th>Profil</th>
                                        <th>CV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {liste.map((apprenant) => (
                                        <tr key={apprenant.id}>
                                            <td>
                                                <Link
                                                    href={apprenantsAdmin.show(
                                                        apprenant.id,
                                                    )}
                                                    className="link-action"
                                                >
                                                    {apprenant.nom}
                                                </Link>
                                                <p
                                                    className="muted"
                                                    style={{ fontSize: 12 }}
                                                >
                                                    {apprenant.email}
                                                </p>
                                            </td>
                                            <td className="muted">
                                                {apprenant.ville ?? '—'}
                                            </td>
                                            <td className="muted">
                                                {apprenant.formations.length > 0
                                                    ? apprenant.formations.join(
                                                          ', ',
                                                      )
                                                    : '—'}
                                            </td>
                                            <td>
                                                <span
                                                    className={`chip ${apprenant.externe ? 'chip-gray' : apprenant.estForme ? 'chip-green' : 'chip-blue'}`}
                                                >
                                                    {apprenant.externe
                                                        ? 'Dossier externe'
                                                        : apprenant.estForme
                                                          ? 'Formé par DAG'
                                                          : 'En parcours'}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    className={`chip ${apprenant.aUnCv ? 'chip-green' : 'chip-gray'}`}
                                                >
                                                    {apprenant.aUnCv
                                                        ? 'Importé'
                                                        : 'Absent'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {onglet === 'recus' && (
                <div className="card" style={{ padding: 24 }}>
                    <p
                        className="muted"
                        style={{ fontSize: 13, marginBottom: 16 }}
                    >
                        Dossiers envoyés à DAG par des candidats (même non
                        formés par DAG) pour que DAG postule à leur place.
                    </p>
                    {dossiersRecus.length === 0 ? (
                        <p className="data-table-empty">
                            Aucun dossier reçu pour le moment.
                        </p>
                    ) : (
                        <div className="data-table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Candidat</th>
                                        <th>Dossier</th>
                                        <th>Reçu le</th>
                                        <th>Statut</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {dossiersRecus.map((dossier) => (
                                        <tr key={dossier.id}>
                                            <td>
                                                <p style={{ fontWeight: 600 }}>
                                                    {dossier.nom}
                                                </p>
                                                <p
                                                    className="muted"
                                                    style={{ fontSize: 12 }}
                                                >
                                                    {dossier.email} ·{' '}
                                                    {dossier.telephone}
                                                </p>
                                            </td>
                                            <td style={{ maxWidth: 280 }}>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 12,
                                                        flexWrap: 'wrap',
                                                    }}
                                                >
                                                    {peutTelecharger && (
                                                        <a
                                                            href={
                                                                candidatsExternesAdmin.cv(
                                                                    dossier.id,
                                                                ).url
                                                            }
                                                            className="link-action"
                                                        >
                                                            Télécharger le CV
                                                        </a>
                                                    )}
                                                    {dossier.portfolio && (
                                                        <a
                                                            href={
                                                                dossier.portfolio
                                                            }
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="link-action"
                                                        >
                                                            Portfolio
                                                        </a>
                                                    )}
                                                    {dossier.apprenantId && (
                                                        <Link
                                                            href={apprenantsAdmin.show(
                                                                dossier.apprenantId,
                                                            )}
                                                            className="link-action"
                                                        >
                                                            Voir le dossier
                                                        </Link>
                                                    )}
                                                </div>
                                                {dossier.message && (
                                                    <p
                                                        className="muted"
                                                        style={{
                                                            fontSize: 12.5,
                                                            marginTop: 6,
                                                            whiteSpace:
                                                                'normal',
                                                        }}
                                                    >
                                                        {dossier.message}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="muted">
                                                {dossier.recuLe ?? '—'}
                                            </td>
                                            <td>
                                                <span
                                                    className={`chip ${dossier.statut === 'NOUVELLE' ? 'chip-orange' : 'chip-green'}`}
                                                >
                                                    {dossier.statut ===
                                                    'NOUVELLE'
                                                        ? 'À traiter'
                                                        : 'Traité'}
                                                </span>
                                            </td>
                                            <td
                                                style={{
                                                    textAlign: 'right',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 8,
                                                        justifyContent:
                                                            'flex-end',
                                                        alignItems: 'center',
                                                        flexWrap: 'wrap',
                                                    }}
                                                >
                                                    {peutPostuler && (
                                                        <>
                                                            <select
                                                                aria-label={`Offre pour ${dossier.nom}`}
                                                                value={
                                                                    offreParDossier[
                                                                        dossier
                                                                            .id
                                                                    ] ?? ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    setOffreParDossier(
                                                                        {
                                                                            ...offreParDossier,
                                                                            [dossier.id]:
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <option value="">
                                                                    {offresDisponibles.length ===
                                                                    0
                                                                        ? 'Aucune offre publiée'
                                                                        : 'Choisir une offre…'}
                                                                </option>
                                                                {offresDisponibles.map(
                                                                    (offre) => (
                                                                        <option
                                                                            key={
                                                                                offre.id
                                                                            }
                                                                            value={
                                                                                offre.id
                                                                            }
                                                                        >
                                                                            {
                                                                                offre.titre
                                                                            }{' '}
                                                                            —{' '}
                                                                            {
                                                                                offre.entrepriseNom
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                            <button
                                                                type="button"
                                                                className="btn-primary"
                                                                style={{
                                                                    padding:
                                                                        '6px 14px',
                                                                    fontSize: 12.5,
                                                                }}
                                                                disabled={
                                                                    !offreParDossier[
                                                                        dossier
                                                                            .id
                                                                    ]
                                                                }
                                                                onClick={() =>
                                                                    postuler(
                                                                        dossier,
                                                                    )
                                                                }
                                                            >
                                                                Postuler
                                                            </button>
                                                        </>
                                                    )}
                                                    {peutSupprimer && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            style={{
                                                                color: 'var(--dag-red)',
                                                            }}
                                                            onClick={() =>
                                                                supprimerDossier(
                                                                    dossier,
                                                                )
                                                            }
                                                        >
                                                            Supprimer
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}
