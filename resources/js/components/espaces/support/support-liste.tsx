import { Head, Link, useForm } from '@inertiajs/react';
import { Headphones, MessageSquare } from 'lucide-react';
import CoordonneesDag from '@/components/coordonnees-dag';
import { formaterDateSupport, SUPPORT_STATUT_CHIP } from './support-format';

export type TicketResume = {
    id: string;
    reference: string;
    sujet: string;
    categorie: string;
    statut: string;
    statutLabel: string;
    dernierMessage: string | null;
    url: string;
};

export type SupportListeProps = {
    tickets: TicketResume[];
    categories: { value: string; label: string }[];
    expediteur: { nom: string; email: string };
    urls: { creer: string };
};

function Erreur({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
            {message}
        </p>
    );
}

/**
 * "Besoin d'aide ?" page of the client and apprenant espaces, laid out like
 * the public contact page: the request form next to DAG's contact details,
 * then the account holder's past requests.
 */
export default function SupportListe({
    tickets,
    categories,
    expediteur,
    urls,
}: SupportListeProps) {
    const form = useForm({
        categorie: categories[0]?.value ?? 'AUTRE',
        sujet: '',
        message: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(urls.creer);
    };

    return (
        <>
            <Head title="Support" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/phone-call-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Besoin d'aide ?</h1>
                    <p>
                        Notre équipe vous répond sous 48h ouvrées, ici dans
                        votre espace et par e-mail.
                    </p>
                </div>
            </div>

            <div className="split-layout" style={{ marginTop: 24 }}>
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
                                <label htmlFor="support-nom">Nom complet</label>
                                <input
                                    id="support-nom"
                                    type="text"
                                    value={expediteur.nom}
                                    readOnly
                                    disabled
                                />
                            </div>
                            <div>
                                <label htmlFor="support-email">E-mail</label>
                                <input
                                    id="support-email"
                                    type="email"
                                    value={expediteur.email}
                                    readOnly
                                    disabled
                                />
                            </div>
                        </div>
                        <div>
                            <label htmlFor="support-categorie">Sujet</label>
                            <select
                                id="support-categorie"
                                value={form.data.categorie}
                                onChange={(event) =>
                                    form.setData(
                                        'categorie',
                                        event.target.value,
                                    )
                                }
                            >
                                {categories.map((categorie) => (
                                    <option
                                        key={categorie.value}
                                        value={categorie.value}
                                    >
                                        {categorie.label}
                                    </option>
                                ))}
                            </select>
                            <Erreur message={form.errors.categorie} />
                        </div>
                        <div>
                            <label htmlFor="support-sujet">Objet</label>
                            <input
                                id="support-sujet"
                                type="text"
                                maxLength={191}
                                value={form.data.sujet}
                                onChange={(event) =>
                                    form.setData('sujet', event.target.value)
                                }
                                placeholder="Ex. : facture DAG-F-2026-0012 en double"
                            />
                            <Erreur message={form.errors.sujet} />
                        </div>
                        <div>
                            <label htmlFor="support-message">Message</label>
                            <textarea
                                id="support-message"
                                rows={5}
                                maxLength={5000}
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                placeholder="Décrivez votre besoin..."
                            />
                            <Erreur message={form.errors.message} />
                        </div>
                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={form.processing}
                        >
                            {form.processing ? 'Envoi…' : 'Envoyer le message'}
                        </button>
                        <p className="form-consent">
                            Vous suivez la réponse dans « Mes demandes »
                            ci-dessous et recevez une notification à chaque
                            message de l'équipe DAG.
                        </p>
                    </form>
                </div>

                <CoordonneesDag />
            </div>

            <h2
                style={{
                    fontSize: 17,
                    color: 'var(--dag-navy)',
                    margin: '36px 0 14px',
                }}
            >
                Mes demandes
            </h2>

            <div className="data-table-wrap">
                {tickets.length === 0 ? (
                    <div
                        className="data-table-empty"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: 10,
                        }}
                    >
                        <Headphones size={28} />
                        <p>
                            Vous n'avez pas encore contacté le support : votre
                            première demande apparaîtra ici.
                        </p>
                    </div>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Objet</th>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th>Dernier message</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {tickets.map((ticket) => (
                                <tr key={ticket.id}>
                                    <td>
                                        <span style={{ fontWeight: 600 }}>
                                            {ticket.sujet}
                                        </span>
                                        <br />
                                        <span
                                            className="muted"
                                            style={{ fontSize: 12 }}
                                        >
                                            {ticket.reference}
                                        </span>
                                    </td>
                                    <td className="muted">
                                        {ticket.categorie}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${SUPPORT_STATUT_CHIP[ticket.statut] ?? 'chip-gray'}`}
                                        >
                                            {ticket.statutLabel}
                                        </span>
                                    </td>
                                    <td className="muted">
                                        {formaterDateSupport(
                                            ticket.dernierMessage,
                                        )}
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        <Link
                                            href={ticket.url}
                                            className="link-action"
                                            style={{
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                gap: 6,
                                            }}
                                        >
                                            <MessageSquare size={14} />
                                            Ouvrir
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}
