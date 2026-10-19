import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import contactRoutes from '@/routes/contact';

export default function Contact() {
    const { data, setData, post, processing, errors, wasSuccessful, reset } =
        useForm({
            nom: '',
            email: '',
            sujet: '',
            message: '',
        });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(contactRoutes.store().url, {
            onSuccess: () => reset(),
        });
    };

    return (
        <PublicLayout>
            <Head title="Contact — DAG" />

            <section className="contact-hero-wrap reveal container">
                <div className="contact-hero">
                    <div className="contact-hero-content">
                        <span className="badge">Contact</span>
                        <h1>Parlons de votre projet</h1>
                        <p>
                            Notre équipe vous répond sous 48h ouvrées pour
                            comprendre votre besoin et vous orienter.
                        </p>
                    </div>
                </div>
            </section>

            <section
                className="section-gap container"
                style={{ marginBottom: 88 }}
            >
                <div className="split-layout">
                    <div className="card" style={{ padding: 32 }}>
                        <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                            Envoyez-nous un message
                        </h2>
                        <form
                            onSubmit={submit}
                            style={{
                                marginTop: 24,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 16,
                            }}
                        >
                            <div>
                                <label htmlFor="nom">Nom complet</label>
                                <input
                                    id="nom"
                                    type="text"
                                    value={data.nom}
                                    onChange={(event) =>
                                        setData('nom', event.target.value)
                                    }
                                />
                                {errors.nom && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.nom}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="email">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                />
                                {errors.email && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="sujet">Sujet</label>
                                <input
                                    id="sujet"
                                    type="text"
                                    value={data.sujet}
                                    onChange={(event) =>
                                        setData('sujet', event.target.value)
                                    }
                                />
                                {errors.sujet && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.sujet}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="message">Message</label>
                                <textarea
                                    id="message"
                                    rows={5}
                                    value={data.message}
                                    onChange={(event) =>
                                        setData('message', event.target.value)
                                    }
                                />
                                {errors.message && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.message}
                                    </p>
                                )}
                            </div>
                            <button
                                type="submit"
                                className="btn-primary"
                                disabled={processing}
                            >
                                {processing ? 'Envoi…' : 'Envoyer le message'}
                            </button>
                            {wasSuccessful && (
                                <p
                                    style={{
                                        color: 'var(--dag-green)',
                                        fontSize: 13,
                                        fontWeight: 600,
                                    }}
                                >
                                    Merci, votre message a bien été envoyé.
                                </p>
                            )}
                        </form>
                    </div>

                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 20,
                        }}
                    >
                        <div className="card" style={{ padding: 24 }}>
                            <h3
                                style={{
                                    fontSize: 15,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                Nos coordonnées
                            </h3>
                            <div
                                style={{
                                    marginTop: 14,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 10,
                                    fontSize: 13.5,
                                }}
                            >
                                <a href="mailto:contact@dag-agency.com">
                                    contact@dag-agency.com
                                </a>
                                <a href="tel:+000000000">+000 00 00 00 00</a>
                            </div>
                        </div>
                        <div className="card" style={{ padding: 24 }}>
                            <h3
                                style={{
                                    fontSize: 15,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                Horaires
                            </h3>
                            <p
                                className="muted"
                                style={{ marginTop: 10, fontSize: 13.5 }}
                            >
                                Du lundi au vendredi, 9h00 — 18h00.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
