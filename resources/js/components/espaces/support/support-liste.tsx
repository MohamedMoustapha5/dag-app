import { Head, Link, useForm } from '@inertiajs/react';
import { Headphones, MessageSquare, Plus, X } from 'lucide-react';
import { useState } from 'react';
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
    urls: { creer: string };
};

/**
 * "Besoin d'aide ?" page of the client and apprenant espaces: the account
 * holder's support requests and the form to open a new one.
 */
export default function SupportListe({
    tickets,
    categories,
    urls,
}: SupportListeProps) {
    const [formulaireOuvert, setFormulaireOuvert] = useState(false);

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
                        <h1>Support</h1>
                        <p>
                            Posez votre question à l'équipe DAG et suivez la
                            réponse ici, sans quitter votre espace.
                        </p>
                    </div>
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => setFormulaireOuvert(true)}
                    >
                        <Plus size={16} />
                        Nouvelle demande
                    </button>
                </div>
            </div>

            <div className="data-table-wrap">
                {tickets.length === 0 ? (
                    <div
                        className="data-table-empty"
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: 12,
                        }}
                    >
                        <Headphones size={28} />
                        <p>Vous n'avez pas encore contacté le support.</p>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={() => setFormulaireOuvert(true)}
                        >
                            Poser une question
                        </button>
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

            {formulaireOuvert && (
                <NouvelleDemande
                    categories={categories}
                    url={urls.creer}
                    onClose={() => setFormulaireOuvert(false)}
                />
            )}
        </>
    );
}

function NouvelleDemande({
    categories,
    url,
    onClose,
}: {
    categories: SupportListeProps['categories'];
    url: string;
    onClose: () => void;
}) {
    const form = useForm({
        categorie: categories[0]?.value ?? 'AUTRE',
        sujet: '',
        message: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(url, { onSuccess: onClose });
    };

    const erreur = (message?: string) =>
        message && (
            <p
                style={{
                    color: 'var(--dag-red)',
                    fontSize: 12,
                    marginTop: 4,
                }}
            >
                {message}
            </p>
        );

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Nouvelle demande de support"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Contacter le support
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 24,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    L'équipe DAG vous répond dans cet espace ; vous êtes aussi
                    prévenu par e-mail.
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="support-categorie">Catégorie</label>
                        <select
                            id="support-categorie"
                            value={form.data.categorie}
                            onChange={(event) =>
                                form.setData('categorie', event.target.value)
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
                        {erreur(form.errors.categorie)}
                    </div>
                    <div>
                        <label htmlFor="support-sujet">Objet</label>
                        <input
                            id="support-sujet"
                            value={form.data.sujet}
                            maxLength={191}
                            placeholder="Ex. : facture DAG-F-2026-0012 en double"
                            onChange={(event) =>
                                form.setData('sujet', event.target.value)
                            }
                        />
                        {erreur(form.errors.sujet)}
                    </div>
                    <div>
                        <label htmlFor="support-message">Message</label>
                        <textarea
                            id="support-message"
                            rows={6}
                            maxLength={5000}
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                        />
                        {erreur(form.errors.message)}
                    </div>
                    <button
                        type="submit"
                        className="btn-primary"
                        disabled={form.processing}
                        style={{ alignSelf: 'flex-end' }}
                    >
                        {form.processing ? 'Envoi…' : 'Envoyer'}
                    </button>
                </form>
            </div>
        </div>
    );
}
