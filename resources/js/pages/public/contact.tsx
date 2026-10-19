import { Head, Link, useForm } from '@inertiajs/react';
import { Clock, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import PublicLayout from '@/layouts/public-layout';
import { contact } from '@/routes';
import contactRoutes from '@/routes/contact';

const SUJETS = [
    'Demander un devis',
    'Question sur une formation',
    'Question sur un abonnement',
    'Support client',
    'Autre demande',
];

const CONTACT_INFO = [
    {
        key: 'whatsapp',
        titre: 'WhatsApp',
        valeur: '+237 6 99 00 00 00',
        href: 'https://wa.me/237699000000',
        icon: MessageCircle,
        iconBg: 'var(--dag-green-bg)',
        iconColor: 'var(--dag-green)',
    },
    {
        key: 'telephone',
        titre: 'Téléphone',
        valeur: '+237 6 99 00 00 00',
        href: 'tel:+237699000000',
        icon: Phone,
        iconBg: 'var(--dag-blue-light)',
        iconColor: 'var(--dag-blue)',
    },
    {
        key: 'email',
        titre: 'E-mail',
        valeur: 'contact@dag.cm',
        href: 'mailto:contact@dag.cm',
        icon: Mail,
        iconBg: 'var(--dag-orange-bg)',
        iconColor: 'var(--dag-orange-ink)',
    },
    {
        key: 'adresse',
        titre: 'Adresse',
        valeur: 'Akwa, Douala, Cameroun',
        href: null,
        icon: MapPin,
        iconBg: '#F1F2F6',
        iconColor: 'var(--dag-text-muted)',
    },
    {
        key: 'horaires',
        titre: 'Horaires',
        valeur: 'Lun – Ven, 8h – 18h',
        href: null,
        icon: Clock,
        iconBg: '#F1F2F6',
        iconColor: 'var(--dag-text-muted)',
    },
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
                                <Link href={contact()}>
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

                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        {CONTACT_INFO.map((info) => {
                            const content = (
                                <>
                                    <span
                                        className="contact-info-icon"
                                        style={{
                                            background: info.iconBg,
                                            color: info.iconColor,
                                        }}
                                    >
                                        <info.icon size={18} />
                                    </span>
                                    <div>
                                        <p className="contact-info-title">
                                            {info.titre}
                                        </p>
                                        <p className="contact-info-value">
                                            {info.valeur}
                                        </p>
                                    </div>
                                </>
                            );

                            return info.href ? (
                                <a
                                    key={info.key}
                                    href={info.href}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="card contact-info-card"
                                >
                                    {content}
                                </a>
                            ) : (
                                <div
                                    key={info.key}
                                    className="card contact-info-card"
                                >
                                    {content}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
