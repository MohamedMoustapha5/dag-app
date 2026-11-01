import { useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect } from 'react';
import { store } from '@/routes/candidatures-spontanees';

type Props = {
    open: boolean;
    onClose: () => void;
};

export default function CandidatureSpontaneeModal({ open, onClose }: Props) {
    const { data, setData, post, processing, errors, wasSuccessful, reset } =
        useForm({
            nom: '',
            email: '',
            telephone: '',
            portfolio: '',
            message: '',
            cv: null as File | null,
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
            aria-label="Envoyer votre dossier de candidature"
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
                    Envoyer votre dossier
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
                    Déjà formé, quel que soit votre domaine — même ailleurs qu'à
                    DAG ? Partagez votre CV, notre équipe vous recontacte dès
                    qu'une opportunité correspond à votre profil.
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
                        Merci, votre dossier a bien été envoyé.
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
                            <label htmlFor="candidature-nom">Nom complet</label>
                            <input
                                id="candidature-nom"
                                type="text"
                                value={data.nom}
                                onChange={(event) =>
                                    setData('nom', event.target.value)
                                }
                                placeholder="Votre nom et prénom"
                                autoFocus
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
                            <label htmlFor="candidature-email">E-mail</label>
                            <input
                                id="candidature-email"
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

                        <div>
                            <label htmlFor="candidature-telephone">
                                Téléphone
                            </label>
                            <input
                                id="candidature-telephone"
                                type="tel"
                                value={data.telephone}
                                onChange={(event) =>
                                    setData('telephone', event.target.value)
                                }
                                placeholder="+237 6XX XXX XXX"
                            />
                            {errors.telephone && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.telephone}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="candidature-message">
                                Domaine ou poste recherché (optionnel)
                            </label>
                            <textarea
                                id="candidature-message"
                                rows={3}
                                value={data.message}
                                onChange={(event) =>
                                    setData('message', event.target.value)
                                }
                                placeholder="Tous domaines acceptés — ex. comptabilité, développement web, design graphique..."
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

                        <div>
                            <label htmlFor="candidature-cv">
                                CV (PDF, Word — 5 Mo max)
                            </label>
                            <input
                                id="candidature-cv"
                                type="file"
                                accept=".pdf,.doc,.docx"
                                onChange={(event) =>
                                    setData(
                                        'cv',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {errors.cv && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.cv}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="candidature-portfolio">
                                Portfolio ou profil en ligne (facultatif)
                            </label>
                            <input
                                id="candidature-portfolio"
                                type="url"
                                value={data.portfolio}
                                onChange={(event) =>
                                    setData('portfolio', event.target.value)
                                }
                                placeholder="https://votre-portfolio.com"
                            />
                            {errors.portfolio && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.portfolio}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={processing}
                        >
                            {processing ? 'Envoi…' : 'Envoyer mon dossier'}
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
}
