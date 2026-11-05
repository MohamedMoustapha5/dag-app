import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { formaterDateSupport, SUPPORT_STATUT_CHIP } from './support-format';

export type MessageFil = {
    id: string;
    deDag: boolean;
    auteur: string;
    contenu: string;
    date: string | null;
};

type Props = {
    ticket: {
        reference: string;
        sujet: string;
        categorie: string;
        statut: string;
        statutLabel?: string;
    };
    messages: MessageFil[];
    /** Links of the page: back to the list, post a reply. */
    lienRetour: { href: string; label: string };
    urlRepondre: string | null;
    /** Extra actions shown next to the status (close, reopen…). */
    actions?: React.ReactNode;
    /** Requester details, shown to the team only. */
    encart?: React.ReactNode;
    /** Whose messages sit on the right: the viewer's side. */
    cote: 'demandeur' | 'equipe';
    titrePage: string;
};

/**
 * A support conversation, shared by the client/apprenant espaces and the
 * admin inbox: the viewer's own messages are on the right.
 */
export default function SupportFil({
    ticket,
    messages,
    lienRetour,
    urlRepondre,
    actions,
    encart,
    cote,
    titrePage,
}: Props) {
    const form = useForm({ message: '' });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (urlRepondre === null) {
            return;
        }

        form.post(urlRepondre, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title={titrePage} />

            <Link
                href={lienRetour.href}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← {lienRetour.label}
            </Link>

            <div
                className="card"
                style={{
                    padding: 24,
                    marginBottom: 20,
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                }}
            >
                <div>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        {ticket.reference} · {ticket.categorie}
                    </p>
                    <h1 style={{ fontSize: 20, marginTop: 4 }}>
                        {ticket.sujet}
                    </h1>
                </div>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        flexWrap: 'wrap',
                    }}
                >
                    <span
                        className={`chip ${SUPPORT_STATUT_CHIP[ticket.statut] ?? 'chip-gray'}`}
                    >
                        {ticket.statutLabel ?? ticket.statut}
                    </span>
                    {actions}
                </div>
            </div>

            {encart}

            <div
                className="card"
                style={{
                    padding: 24,
                    marginBottom: 20,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 14,
                }}
            >
                {messages.map((message) => {
                    const aDroite =
                        cote === 'equipe' ? message.deDag : !message.deDag;

                    return (
                        <div
                            key={message.id}
                            style={{
                                alignSelf: aDroite ? 'flex-end' : 'flex-start',
                                maxWidth: 'min(640px, 90%)',
                                background: aDroite
                                    ? 'var(--dag-blue-light)'
                                    : 'var(--dag-bg)',
                                borderRadius: 12,
                                padding: '12px 16px',
                            }}
                        >
                            <p
                                style={{
                                    fontSize: 12,
                                    fontWeight: 700,
                                    marginBottom: 4,
                                }}
                            >
                                {message.auteur}
                                <span
                                    className="muted"
                                    style={{ fontWeight: 400, marginLeft: 8 }}
                                >
                                    {formaterDateSupport(message.date)}
                                </span>
                            </p>
                            <p
                                style={{
                                    fontSize: 14,
                                    lineHeight: 1.6,
                                    whiteSpace: 'pre-line',
                                    overflowWrap: 'anywhere',
                                }}
                            >
                                {message.contenu}
                            </p>
                        </div>
                    );
                })}
            </div>

            {urlRepondre !== null && (
                <form
                    onSubmit={submit}
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 12,
                    }}
                >
                    <label htmlFor="support-reponse">
                        {ticket.statut === 'CLOS'
                            ? 'Écrire pour rouvrir la demande'
                            : 'Votre message'}
                    </label>
                    <textarea
                        id="support-reponse"
                        rows={4}
                        maxLength={5000}
                        value={form.data.message}
                        onChange={(event) =>
                            form.setData('message', event.target.value)
                        }
                    />
                    {form.errors.message && (
                        <p
                            style={{
                                color: 'var(--dag-red)',
                                fontSize: 12,
                            }}
                        >
                            {form.errors.message}
                        </p>
                    )}
                    <button
                        type="submit"
                        className="btn-primary"
                        disabled={
                            form.processing || form.data.message.trim() === ''
                        }
                        style={{ alignSelf: 'flex-end' }}
                    >
                        {form.processing ? 'Envoi…' : 'Envoyer'}
                    </button>
                </form>
            )}
        </>
    );
}

/**
 * "Problème résolu" button of the requester side.
 */
export function BoutonClore({ url }: { url: string }) {
    return (
        <button
            type="button"
            className="btn-ghost"
            onClick={() => router.patch(url, {}, { preserveScroll: true })}
            style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}
        >
            <CheckCircle2 size={16} />
            Problème résolu
        </button>
    );
}
