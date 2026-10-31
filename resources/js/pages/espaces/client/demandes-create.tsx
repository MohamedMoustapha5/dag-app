import { Head, useForm } from '@inertiajs/react';
import demandes from '@/routes/espace-client/demandes';

type Service = { id: string; nom: string; categorie: string };
type Formule = { id: string; nom: string; prix_affiche: string | null };

type Props = {
    services: Service[];
    formules: Formule[];
    serviceSelectionne: string | null;
};

export default function DemandesCreate({
    services,
    formules,
    serviceSelectionne,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        type: 'service' as 'service' | 'formule',
        service_id: serviceSelectionne ?? '',
        formule_id: '',
        description: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(demandes.store().url);
    };

    return (
        <>
            <Head title="Nouvelle demande" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 24,
                }}
            >
                Nouvelle demande
            </h1>

            <div className="card" style={{ padding: 32, maxWidth: 560 }}>
                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="type">Type de demande</label>
                        <select
                            id="type"
                            value={data.type}
                            onChange={(event) =>
                                setData(
                                    'type',
                                    event.target.value as 'service' | 'formule',
                                )
                            }
                        >
                            <option value="service">Un service ponctuel</option>
                            <option value="formule">Un abonnement</option>
                        </select>
                    </div>

                    {data.type === 'service' ? (
                        <div>
                            <label htmlFor="service_id">Service souhaité</label>
                            <select
                                id="service_id"
                                value={data.service_id}
                                onChange={(event) =>
                                    setData('service_id', event.target.value)
                                }
                            >
                                <option value="">
                                    Sélectionnez un service
                                </option>
                                {services.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.nom} — {service.categorie}
                                    </option>
                                ))}
                            </select>
                            {errors.service_id && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.service_id}
                                </p>
                            )}
                        </div>
                    ) : (
                        <div>
                            <label htmlFor="formule_id">
                                Formule souhaitée
                            </label>
                            <select
                                id="formule_id"
                                value={data.formule_id}
                                onChange={(event) =>
                                    setData('formule_id', event.target.value)
                                }
                            >
                                <option value="">
                                    Sélectionnez une formule
                                </option>
                                {formules.map((formule) => (
                                    <option key={formule.id} value={formule.id}>
                                        {formule.nom}
                                        {formule.prix_affiche
                                            ? ` — ${formule.prix_affiche}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            {errors.formule_id && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.formule_id}
                                </p>
                            )}
                        </div>
                    )}

                    <div>
                        <label htmlFor="description">
                            Décrivez votre besoin
                        </label>
                        <textarea
                            id="description"
                            rows={5}
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            placeholder="Expliquez votre projet, vos attentes, vos délais..."
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

                    <button
                        type="submit"
                        className="btn-primary btn-block"
                        disabled={processing}
                    >
                        {processing ? 'Envoi…' : 'Envoyer ma demande'}
                    </button>
                </form>
            </div>
        </>
    );
}
