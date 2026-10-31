import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import offres from '@/routes/espace-client/offres';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type CandidatureApercu = {
    id: string;
    apprenantNom: string;
    formationSuivie: string | null;
    statut: string;
};

type Offre = {
    id: string;
    titre: string;
    statut: string;
    dateLimite: string | null;
    candidaturesCount: number;
    candidaturesApercu: CandidatureApercu[];
};

type Props = {
    offres: Offre[];
};

function formatDateCourte(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    const date = new Date(`${dateIso}T00:00:00`);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export default function OffresIndex({ offres: offresList }: Props) {
    return (
        <>
            <Head title="Mes offres" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/presentation-data-review.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Mes offres</h1>
                        <p>
                            Gérez vos offres de stage et d'emploi, et suivez les
                            candidatures.
                        </p>
                    </div>
                    <Link href={offres.create()} className="btn-primary">
                        <Plus size={16} />
                        Publier une offre
                    </Link>
                </div>
            </div>

            {offresList.length === 0 ? (
                <div
                    className="card"
                    style={{ padding: 40, textAlign: 'center' }}
                >
                    <p
                        className="muted"
                        style={{ fontSize: 13.5, marginBottom: 16 }}
                    >
                        Vous n'avez pas encore publié d'offre.
                    </p>
                    <Link href={offres.create()} className="btn-primary">
                        Publier ma première offre
                    </Link>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 20,
                    }}
                >
                    {offresList.map((offre) => (
                        <div
                            key={offre.id}
                            className="card"
                            style={{ padding: 24 }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                    flexWrap: 'wrap',
                                    marginBottom: 6,
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                    }}
                                >
                                    <p
                                        style={{
                                            fontWeight: 700,
                                            fontSize: 15,
                                        }}
                                    >
                                        {offre.titre}
                                    </p>
                                    <span
                                        className={`chip ${STATUT_CHIP[offre.statut] ?? 'chip-gray'}`}
                                    >
                                        {offre.statut === 'PUBLIEE'
                                            ? 'Active'
                                            : (STATUT_LABEL[offre.statut] ??
                                              offre.statut)}
                                    </span>
                                </div>
                                <Link
                                    href={offres.show(offre.id)}
                                    className="btn-outline"
                                >
                                    Voir les candidatures
                                </Link>
                            </div>
                            <p
                                className="muted"
                                style={{ fontSize: 12.5, marginBottom: 16 }}
                            >
                                {offre.dateLimite &&
                                    `Date limite : ${formatDateCourte(offre.dateLimite)} · `}
                                {offre.candidaturesCount} candidature
                                {offre.candidaturesCount > 1 ? 's' : ''}
                            </p>

                            {offre.candidaturesApercu.length > 0 && (
                                <div className="data-table-wrap">
                                    <table className="data-table">
                                        <thead>
                                            <tr>
                                                <th>Apprenant</th>
                                                <th>Formation suivie</th>
                                                <th>Statut</th>
                                                <th />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {offre.candidaturesApercu.map(
                                                (candidature) => (
                                                    <tr key={candidature.id}>
                                                        <td
                                                            style={{
                                                                fontWeight: 600,
                                                            }}
                                                        >
                                                            {
                                                                candidature.apprenantNom
                                                            }
                                                        </td>
                                                        <td className="muted">
                                                            {candidature.formationSuivie ??
                                                                '—'}
                                                        </td>
                                                        <td>
                                                            <span
                                                                className={`chip ${STATUT_CHIP[candidature.statut] ?? 'chip-gray'}`}
                                                            >
                                                                {STATUT_LABEL[
                                                                    candidature
                                                                        .statut
                                                                ] ??
                                                                    candidature.statut}
                                                            </span>
                                                        </td>
                                                        <td
                                                            style={{
                                                                textAlign:
                                                                    'right',
                                                            }}
                                                        >
                                                            <Link
                                                                href={offres.show(
                                                                    offre.id,
                                                                )}
                                                                className="link-action"
                                                            >
                                                                {candidature.statut ===
                                                                'EN_ETUDE'
                                                                    ? 'Sélectionner'
                                                                    : 'Voir le profil'}
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
