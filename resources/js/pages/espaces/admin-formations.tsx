import { Head, Link, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import formationsAdmin from '@/routes/espace-admin/formations';

type Formation = {
    id: string;
    nom: string;
    categorie: string | null;
    statut: string;
    sessionsCount: number;
};

type Props = {
    formations: Formation[];
    peutCreer: boolean;
};

export default function AdminFormations({ formations, peutCreer }: Props) {
    const form = useForm({
        nom: '',
        description: '',
        categorie: '',
        duree: '',
        prerequis: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(formationsAdmin.store().url);
    };

    return (
        <>
            <Head title="Formations — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/formation-digital-marketing.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Formations</h1>
                    <p>
                        Gérez le catalogue de formations, leurs sessions et
                        leurs séances.
                    </p>
                </div>
            </div>

            {peutCreer && (
                <form
                    onSubmit={submit}
                    className="card"
                    style={{
                        padding: 24,
                        marginBottom: 28,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 14,
                    }}
                >
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Créer une formation
                    </p>
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(220px, 1fr))',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="nom">Nom</label>
                            <input
                                id="nom"
                                value={form.data.nom}
                                onChange={(event) =>
                                    form.setData('nom', event.target.value)
                                }
                            />
                            {form.errors.nom && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.nom}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="categorie">Catégorie</label>
                            <input
                                id="categorie"
                                value={form.data.categorie}
                                onChange={(event) =>
                                    form.setData(
                                        'categorie',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="duree">Durée</label>
                            <input
                                id="duree"
                                placeholder="Ex. 3 semaines"
                                value={form.data.duree}
                                onChange={(event) =>
                                    form.setData('duree', event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="prerequis">Prérequis</label>
                            <input
                                id="prerequis"
                                placeholder="Ex. Aucun"
                                value={form.data.prerequis}
                                onChange={(event) =>
                                    form.setData(
                                        'prerequis',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="description">Description</label>
                            <textarea
                                id="description"
                                rows={3}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.description && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.description}
                                </p>
                            )}
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing ? 'Création…' : 'Créer la formation'}
                    </button>
                </form>
            )}

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Formations ({formations.length})
                </p>
                {formations.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune formation pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Formation</th>
                                    <th>Catégorie</th>
                                    <th>Sessions</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {formations.map((formation) => (
                                    <tr key={formation.id}>
                                        <td>
                                            <Link
                                                href={formationsAdmin.show(
                                                    formation.id,
                                                )}
                                                className="link-action"
                                            >
                                                {formation.nom}
                                            </Link>
                                        </td>
                                        <td className="muted">
                                            {formation.categorie ?? '—'}
                                        </td>
                                        <td className="muted">
                                            {formation.sessionsCount}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[formation.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[
                                                    formation.statut
                                                ] ?? formation.statut}
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
