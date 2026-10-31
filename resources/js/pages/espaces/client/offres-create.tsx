import { Head, useForm } from '@inertiajs/react';
import offres from '@/routes/espace-client/offres';

export default function OffresCreate() {
    const { data, setData, post, processing, errors } = useForm({
        titre: '',
        description: '',
        type_contrat: 'STAGE' as 'STAGE' | 'CDD' | 'CDI',
        duree: '',
        competences: '',
        ville: '',
        date_limite: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(offres.store().url);
    };

    return (
        <>
            <Head title="Publier une offre" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 24,
                }}
            >
                Publier une offre
            </h1>

            <div className="card" style={{ padding: 32, maxWidth: 640 }}>
                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="titre">Titre du poste</label>
                        <input
                            id="titre"
                            type="text"
                            value={data.titre}
                            onChange={(event) =>
                                setData('titre', event.target.value)
                            }
                            placeholder="Ex. Community Manager junior — Stage 3 mois"
                        />
                        {errors.titre && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {errors.titre}
                            </p>
                        )}
                    </div>

                    <div className="form-row-2">
                        <div>
                            <label htmlFor="type_contrat">
                                Type de contrat
                            </label>
                            <select
                                id="type_contrat"
                                value={data.type_contrat}
                                onChange={(event) =>
                                    setData(
                                        'type_contrat',
                                        event.target.value as
                                            | 'STAGE'
                                            | 'CDD'
                                            | 'CDI',
                                    )
                                }
                            >
                                <option value="STAGE">Stage</option>
                                <option value="CDD">CDD</option>
                                <option value="CDI">CDI</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="duree">Durée</label>
                            <input
                                id="duree"
                                type="text"
                                value={data.duree}
                                onChange={(event) =>
                                    setData('duree', event.target.value)
                                }
                                placeholder="Ex. 3 mois"
                            />
                        </div>
                    </div>

                    <div className="form-row-2">
                        <div>
                            <label htmlFor="ville">Ville</label>
                            <input
                                id="ville"
                                type="text"
                                value={data.ville}
                                onChange={(event) =>
                                    setData('ville', event.target.value)
                                }
                                placeholder="Douala"
                            />
                        </div>
                        <div>
                            <label htmlFor="date_limite">
                                Date limite de candidature
                            </label>
                            <input
                                id="date_limite"
                                type="date"
                                value={data.date_limite}
                                onChange={(event) =>
                                    setData('date_limite', event.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="description">
                            Description du poste
                        </label>
                        <textarea
                            id="description"
                            rows={5}
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            placeholder="Missions, profil recherché, conditions..."
                        />
                        {errors.description && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="competences">
                            Compétences souhaitées
                        </label>
                        <textarea
                            id="competences"
                            rows={3}
                            value={data.competences}
                            onChange={(event) =>
                                setData('competences', event.target.value)
                            }
                            placeholder="Ex. Community management, Canva, rédaction..."
                        />
                    </div>

                    <button
                        type="submit"
                        className="btn-primary btn-block"
                        disabled={processing}
                    >
                        {processing ? 'Publication…' : "Publier l'offre"}
                    </button>
                </form>
            </div>
        </>
    );
}
