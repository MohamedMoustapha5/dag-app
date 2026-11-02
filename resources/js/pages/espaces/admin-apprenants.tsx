import { Head, Link } from '@inertiajs/react';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';

type Apprenant = {
    id: string;
    nom: string;
    email: string;
    ville: string | null;
    inscriptionsCount: number;
    aUnCv: boolean;
};

type Props = {
    apprenants: Apprenant[];
};

export default function AdminApprenants({ apprenants }: Props) {
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
                        Profils, formations suivies et candidatures de chaque
                        apprenant.
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Apprenants ({apprenants.length})
                </p>
                {apprenants.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun apprenant pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Apprenant</th>
                                    <th>Ville</th>
                                    <th>Inscriptions</th>
                                    <th>CV</th>
                                </tr>
                            </thead>
                            <tbody>
                                {apprenants.map((apprenant) => (
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
                                            {apprenant.inscriptionsCount}
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
        </>
    );
}
