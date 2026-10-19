import { useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect } from 'react';
import { store } from '@/routes/audit-requests';

type Props = {
    open: boolean;
    onClose: () => void;
};

export default function AuditRequestModal({ open, onClose }: Props) {
    const { data, setData, post, processing, errors, wasSuccessful, reset } =
        useForm({
            page_url: '',
            email: '',
        });

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(store().url, { onSuccess: () => reset() });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Demander un audit gratuit"
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
                    Audit gratuit de votre page
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
                    Indiquez l'adresse de votre site ou de votre page, notre
                    équipe l'analyse et vous recontacte sous 48h — sans créer de
                    compte.
                </p>

                {wasSuccessful ? (
                    <p
                        style={{
                            fontSize: 13.5,
                            fontWeight: 600,
                            color: 'var(--dag-green)',
                            textAlign: 'center',
                        }}
                    >
                        Merci, votre demande a bien été envoyée.
                    </p>
                ) : (
                    <form
                        onSubmit={submit}
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        <div>
                            <label htmlFor="audit-page-url">
                                URL de votre page
                            </label>
                            <input
                                id="audit-page-url"
                                type="text"
                                value={data.page_url}
                                onChange={(event) =>
                                    setData('page_url', event.target.value)
                                }
                                placeholder="https://votre-site.com"
                                autoFocus
                            />
                            {errors.page_url && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.page_url}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="audit-email">E-mail</label>
                            <input
                                id="audit-email"
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

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={processing}
                        >
                            {processing ? 'Envoi…' : "Demander l'audit gratuit"}
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
}
