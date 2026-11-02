import { Head, router } from '@inertiajs/react';
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
};

type Props = {
    entreprises: Entreprise[];
    offres: Offre[];
    peutValider: boolean;
    peutModifier: boolean;
};

export default function AdminEntreprises({
    entreprises,
    offres,
    peutValider,
    peutModifier,
}: Props) {
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
                                                className={`chip ${offre.statut === 'PUBLIEE' ? 'chip-green' : (STATUT_CHIP[offre.statut] ?? 'chip-gray')}`}
                                            >
                                                {offre.statut === 'PUBLIEE'
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
        </>
    );
}
