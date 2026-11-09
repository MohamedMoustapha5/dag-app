import { Head, Link, useForm } from '@inertiajs/react';
import CoordonneesDag from '@/components/coordonnees-dag';
import PublicLayout from '@/layouts/public-layout';
import { politiqueConfidentialite } from '@/routes';
import contactRoutes from '@/routes/contact';

const SUJETS = [
    'Demander un devis',
    'Question sur une formation',
    'Question sur un abonnement',
    'Support client',
    'Autre demande',
];

export default function Contact() {
    const { data, setData, post, processing, errors, wasSuccessful, reset } =
        useForm({
            nom: '',
            email: '',
            sujet: SUJETS[0],
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
                    <div className="contact-hero-overlay" />
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
                        <form
                            onSubmit={submit}
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 16,
                            }}
                        >
                            <div className="form-row-2">
                                <div>
                                    <label htmlFor="nom">Nom complet</label>
                                    <input
                                        id="nom"
                                        type="text"
                                        value={data.nom}
                                        onChange={(event) =>
                                            setData('nom', event.target.value)
                                        }
                                        placeholder="Votre nom"
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
                                    <label htmlFor="email">E-mail</label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData('email', event.target.value)
                                        }
                                        placeholder="vous@exemple.com"
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
                            </div>
                            <div>
                                <label htmlFor="sujet">Sujet</label>
                                <select
                                    id="sujet"
                                    value={data.sujet}
                                    onChange={(event) =>
                                        setData('sujet', event.target.value)
                                    }
                                >
                                    {SUJETS.map((sujet) => (
                                        <option key={sujet} value={sujet}>
                                            {sujet}
                                        </option>
                                    ))}
                                </select>
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
                                    placeholder="Décrivez votre besoin..."
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
                                className="btn-primary btn-block"
                                disabled={processing}
                            >
                                {processing ? 'Envoi…' : 'Envoyer le message'}
                            </button>
                            <p className="form-consent">
                                En envoyant ce formulaire, vous acceptez la{' '}
                                <Link href={politiqueConfidentialite()}>
                                    politique de confidentialité
                                </Link>{' '}
                                de DAG.
                            </p>
                            {wasSuccessful && (
                                <p
                                    style={{
                                        color: 'var(--dag-green)',
                                        fontSize: 13,
                                        fontWeight: 600,
                                        textAlign: 'center',
                                    }}
                                >
                                    Merci, votre message a bien été envoyé.
                                </p>
                            )}
                        </form>
                    </div>

                    <CoordonneesDag />
                </div>
            </section>
        </PublicLayout>
    );
}
