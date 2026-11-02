import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';

type Apprenant = {
    id: string;
    nom: string;
    email: string;
    ville: string | null;
    inscriptionsCount: number;
    estForme: boolean;
    formations: string[];
    attestationsCount: number;
    aUnCv: boolean;
};

type Props = {
    apprenants: Apprenant[];
};

type Filtre = 'tous' | 'en-formation' | 'formes';

const FILTRES: { value: Filtre; label: string }[] = [
    { value: 'tous', label: 'Tous' },
    { value: 'en-formation', label: 'En formation' },
    { value: 'formes', label: 'Déjà formés' },
];

export default function AdminApprenants({ apprenants }: Props) {
    const [filtre, setFiltre] = useState<Filtre>('tous');
    const [recherche, setRecherche] = useState('');

    const liste = useMemo(() => {
        const terme = recherche.trim().toLowerCase();

        return apprenants.filter((apprenant) => {
            if (filtre === 'formes' && !apprenant.estForme) {
                return false;
            }

            if (filtre === 'en-formation' && apprenant.estForme) {
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
                        apprenant, anciens apprenants déjà formés inclus.
                        Postulez à une offre depuis le dossier.
                    </p>
                </div>
            </div>

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
                                style={{ padding: '6px 14px', fontSize: 12.5 }}
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
                                                className={`chip ${apprenant.estForme ? 'chip-green' : 'chip-gray'}`}
                                            >
                                                {apprenant.estForme
                                                    ? 'Formé'
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
        </>
    );
}
