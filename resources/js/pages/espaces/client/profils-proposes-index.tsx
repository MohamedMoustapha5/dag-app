import { Head, router } from '@inertiajs/react';
import profilsProposes from '@/routes/espace-client/profils-proposes';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Profil = {
    id: string;
    apprenantNom: string;
    descriptif: string | null;
    statut: string;
    proposeLe: string;
};

type Props = {
    profils: Profil[];
};

function formatDateCourte(dateIso: string): string {
    const date = new Date(`${dateIso}T00:00:00`);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date);
}

export default function ProfilsProposesIndex({ profils }: Props) {
    const decider = (profilId: string, statut: 'RETENU' | 'REFUSE') => {
        router.patch(
            profilsProposes.update(profilId).url,
            { statut },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Profils proposés par DAG" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 8,
                }}
            >
                Profils proposés par DAG
            </h1>
            <p className="muted" style={{ fontSize: 13.5, marginBottom: 24 }}>
                Des profils d'apprenants que notre équipe a sélectionnés pour
                vos besoins.
            </p>

            {profils.length === 0 ? (
                <div
                    className="card"
                    style={{ padding: 40, textAlign: 'center' }}
                >
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Aucun profil proposé pour le moment.
                    </p>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 12,
                    }}
                >
                    {profils.map((profil) => (
                        <div
                            key={profil.id}
                            className="card"
                            style={{
                                padding: 20,
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                gap: 16,
                                flexWrap: 'wrap',
                            }}
                        >
                            <div>
                                <p style={{ fontWeight: 700, fontSize: 14.5 }}>
                                    {profil.apprenantNom}
                                </p>
                                {profil.descriptif && (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12.5, marginTop: 2 }}
                                    >
                                        {profil.descriptif} — proposé le{' '}
                                        {formatDateCourte(profil.proposeLe)}
                                    </p>
                                )}
                            </div>
                            {profil.statut === 'PROPOSE' ? (
                                <div style={{ display: 'flex', gap: 10 }}>
                                    <button
                                        type="button"
                                        className="btn-outline"
                                        onClick={() =>
                                            decider(profil.id, 'REFUSE')
                                        }
                                    >
                                        Non merci
                                    </button>
                                    <button
                                        type="button"
                                        className="btn-primary"
                                        onClick={() =>
                                            decider(profil.id, 'RETENU')
                                        }
                                    >
                                        Sélectionner
                                    </button>
                                </div>
                            ) : (
                                <span
                                    className={`chip ${STATUT_CHIP[profil.statut] ?? 'chip-gray'}`}
                                >
                                    {STATUT_LABEL[profil.statut] ??
                                        profil.statut}
                                </span>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
