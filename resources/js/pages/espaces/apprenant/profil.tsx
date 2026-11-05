import { Form, Head, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import profilRoutes from '@/routes/espace-apprenant/profil';
import { update as updatePassword } from '@/routes/user-password';

type Props = {
    apprenant: EspaceApprenantInfo;
    profil: {
        nom: string;
        email: string;
        telephone: string | null;
        ville: string | null;
        dateNaissance: string | null;
    };
    notifications: {
        seances: boolean;
        candidatures: boolean;
        newsletter: boolean;
    };
};

type Onglet = 'infos' | 'securite' | 'notifications';

const ONGLETS: { value: Onglet; label: string }[] = [
    { value: 'infos', label: 'Informations personnelles' },
    { value: 'securite', label: 'Sécurité' },
    { value: 'notifications', label: 'Notifications' },
];

export default function ApprenantProfil({
    apprenant,
    profil,
    notifications,
}: Props) {
    const [onglet, setOnglet] = useState<Onglet>('infos');
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const deletePasswordInput = useRef<HTMLInputElement>(null);

    const infosForm = useForm({
        nom: profil.nom,
        email: profil.email,
        telephone: profil.telephone ?? '',
        ville: profil.ville ?? '',
        date_naissance: profil.dateNaissance ?? '',
        current_password: '',
    });

    const emailModifie =
        infosForm.data.email.trim().toLowerCase() !==
        profil.email.toLowerCase();

    const notifForm = useForm({
        notif_seances: notifications.seances,
        notif_candidatures: notifications.candidatures,
        notif_newsletter: notifications.newsletter,
    });

    const submitInfos = (event: React.FormEvent) => {
        event.preventDefault();
        infosForm.patch(profilRoutes.update().url, {
            onFinish: () => infosForm.reset('current_password'),
        });
    };

    const submitNotifications = (event: React.FormEvent) => {
        event.preventDefault();
        notifForm.patch(profilRoutes.notifications().url);
    };

    const initiales = apprenant.nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');

    return (
        <>
            <Head title="Profil" />

            <div className="account-banner">
                <div className="account-banner-identity">
                    <span className="account-banner-avatar">{initiales}</span>
                    <div>
                        <span className="badge badge-dark">
                            Profil apprenant
                        </span>
                        <h1>{apprenant.nom}</h1>
                        <p className="account-banner-sub">
                            {apprenant.typeLabel} DAG
                            {profil.ville ? ` · ${profil.ville}` : ''}
                        </p>
                    </div>
                </div>
                <span className="chip chip-green">Compte actif</span>
            </div>

            <div className="account-tabs">
                {ONGLETS.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        className={`account-tab${onglet === item.value ? ' active' : ''}`}
                        onClick={() => setOnglet(item.value)}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            <div className="grid-3">
                <div
                    className="card"
                    style={{ padding: 28, gridColumn: 'span 2' }}
                >
                    {onglet === 'infos' && (
                        <>
                            <h2 style={{ fontSize: 15, marginBottom: 20 }}>
                                Informations personnelles
                            </h2>
                            <form
                                onSubmit={submitInfos}
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
                                            value={infosForm.data.nom}
                                            onChange={(event) =>
                                                infosForm.setData(
                                                    'nom',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {infosForm.errors.nom && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {infosForm.errors.nom}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label htmlFor="telephone">
                                            Téléphone
                                        </label>
                                        <input
                                            id="telephone"
                                            type="text"
                                            value={infosForm.data.telephone}
                                            onChange={(event) =>
                                                infosForm.setData(
                                                    'telephone',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="+237 6 55 00 11 22"
                                        />
                                        {infosForm.errors.telephone && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {infosForm.errors.telephone}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <div>
                                    <label htmlFor="email">
                                        Adresse e-mail
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={infosForm.data.email}
                                        onChange={(event) =>
                                            infosForm.setData(
                                                'email',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {infosForm.errors.email && (
                                        <p
                                            style={{
                                                color: 'var(--dag-red)',
                                                fontSize: 12,
                                                marginTop: 4,
                                            }}
                                        >
                                            {infosForm.errors.email}
                                        </p>
                                    )}
                                </div>
                                {emailModifie && (
                                    <div>
                                        <label htmlFor="email_current_password">
                                            Mot de passe actuel
                                        </label>
                                        <input
                                            id="email_current_password"
                                            type="password"
                                            autoComplete="current-password"
                                            value={
                                                infosForm.data.current_password
                                            }
                                            onChange={(event) =>
                                                infosForm.setData(
                                                    'current_password',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <p
                                            className="muted"
                                            style={{
                                                fontSize: 12,
                                                marginTop: 4,
                                            }}
                                        >
                                            Requis pour changer d'adresse
                                            e-mail. Un lien de vérification
                                            sera envoyé à la nouvelle adresse.
                                        </p>
                                        {infosForm.errors.current_password && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {
                                                    infosForm.errors
                                                        .current_password
                                                }
                                            </p>
                                        )}
                                    </div>
                                )}
                                <div className="form-row-2">
                                    <div>
                                        <label htmlFor="ville">Ville</label>
                                        <input
                                            id="ville"
                                            type="text"
                                            value={infosForm.data.ville}
                                            onChange={(event) =>
                                                infosForm.setData(
                                                    'ville',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Douala"
                                        />
                                        {infosForm.errors.ville && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {infosForm.errors.ville}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label htmlFor="date_naissance">
                                            Date de naissance
                                        </label>
                                        <input
                                            id="date_naissance"
                                            type="date"
                                            value={
                                                infosForm.data.date_naissance
                                            }
                                            onChange={(event) =>
                                                infosForm.setData(
                                                    'date_naissance',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {infosForm.errors.date_naissance && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {
                                                    infosForm.errors
                                                        .date_naissance
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    className="btn-primary"
                                    style={{ alignSelf: 'flex-start' }}
                                    disabled={infosForm.processing}
                                >
                                    {infosForm.processing
                                        ? 'Enregistrement…'
                                        : 'Enregistrer les modifications'}
                                </button>
                                {infosForm.recentlySuccessful && (
                                    <p
                                        style={{
                                            fontSize: 12.5,
                                            color: 'var(--dag-green)',
                                        }}
                                    >
                                        Informations mises à jour.
                                    </p>
                                )}
                            </form>
                        </>
                    )}

                    {onglet === 'securite' && (
                        <>
                            <h2 style={{ fontSize: 15, marginBottom: 20 }}>
                                Sécurité
                            </h2>
                            <Form
                                {...updatePassword.form()}
                                resetOnError={[
                                    'password',
                                    'password_confirmation',
                                    'current_password',
                                ]}
                                resetOnSuccess
                                onError={(errors) => {
                                    if (errors.current_password) {
                                        currentPasswordInput.current?.focus();
                                    }
                                }}
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 16,
                                    maxWidth: 420,
                                }}
                            >
                                {({
                                    errors,
                                    processing,
                                    recentlySuccessful,
                                }) => (
                                    <>
                                        <div>
                                            <label htmlFor="current_password">
                                                Mot de passe actuel
                                            </label>
                                            <input
                                                id="current_password"
                                                ref={currentPasswordInput}
                                                type="password"
                                                name="current_password"
                                                autoComplete="current-password"
                                            />
                                            {errors.current_password && (
                                                <p
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                        fontSize: 12,
                                                        marginTop: 4,
                                                    }}
                                                >
                                                    {errors.current_password}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label htmlFor="password">
                                                Nouveau mot de passe
                                            </label>
                                            <input
                                                id="password"
                                                type="password"
                                                name="password"
                                                autoComplete="new-password"
                                            />
                                            {errors.password && (
                                                <p
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                        fontSize: 12,
                                                        marginTop: 4,
                                                    }}
                                                >
                                                    {errors.password}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label htmlFor="password_confirmation">
                                                Confirmer le mot de passe
                                            </label>
                                            <input
                                                id="password_confirmation"
                                                type="password"
                                                name="password_confirmation"
                                                autoComplete="new-password"
                                            />
                                            {errors.password_confirmation && (
                                                <p
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                        fontSize: 12,
                                                        marginTop: 4,
                                                    }}
                                                >
                                                    {
                                                        errors.password_confirmation
                                                    }
                                                </p>
                                            )}
                                        </div>
                                        <button
                                            type="submit"
                                            className="btn-primary"
                                            style={{ alignSelf: 'flex-start' }}
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Mise à jour…'
                                                : 'Mettre à jour le mot de passe'}
                                        </button>
                                        {recentlySuccessful && (
                                            <p
                                                style={{
                                                    fontSize: 12.5,
                                                    color: 'var(--dag-green)',
                                                }}
                                            >
                                                Mot de passe mis à jour.
                                            </p>
                                        )}
                                    </>
                                )}
                            </Form>
                        </>
                    )}

                    {onglet === 'notifications' && (
                        <>
                            <h2 style={{ fontSize: 15, marginBottom: 20 }}>
                                Notifications
                            </h2>
                            <form
                                onSubmit={submitNotifications}
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 14,
                                }}
                            >
                                <label
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                        fontSize: 13.5,
                                    }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={notifForm.data.notif_seances}
                                        onChange={(event) =>
                                            notifForm.setData(
                                                'notif_seances',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Rappels de séance et emploi du temps
                                </label>
                                <label
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                        fontSize: 13.5,
                                    }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={
                                            notifForm.data.notif_candidatures
                                        }
                                        onChange={(event) =>
                                            notifForm.setData(
                                                'notif_candidatures',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Mise à jour de candidature
                                </label>
                                <label
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                        fontSize: 13.5,
                                    }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={
                                            notifForm.data.notif_newsletter
                                        }
                                        onChange={(event) =>
                                            notifForm.setData(
                                                'notif_newsletter',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Nouvelles formations et actualités DAG
                                </label>
                                <button
                                    type="submit"
                                    className="btn-primary"
                                    style={{
                                        alignSelf: 'flex-start',
                                        marginTop: 6,
                                    }}
                                    disabled={notifForm.processing}
                                >
                                    {notifForm.processing
                                        ? 'Enregistrement…'
                                        : 'Enregistrer'}
                                </button>
                            </form>
                        </>
                    )}
                </div>

                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    <div className="card" style={{ padding: 24 }}>
                        <p style={{ fontSize: 14, fontWeight: 700 }}>
                            {apprenant.nom}
                        </p>
                        <p
                            className="muted"
                            style={{ fontSize: 12.5, marginTop: 4 }}
                        >
                            {apprenant.typeLabel}
                        </p>
                    </div>

                    <div className="card danger-zone" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 14,
                                fontWeight: 700,
                                color: 'var(--dag-red)',
                                marginBottom: 8,
                            }}
                        >
                            Zone sensible
                        </p>
                        <p
                            className="muted"
                            style={{
                                fontSize: 12.5,
                                lineHeight: 1.6,
                                marginBottom: 16,
                            }}
                        >
                            La suppression de votre compte est définitive. Votre
                            attestation déjà délivrée reste archivée,
                            conformément à la politique de confidentialité.
                        </p>
                        <button
                            type="button"
                            className="btn-outline"
                            style={{
                                borderColor: 'var(--dag-red)',
                                color: 'var(--dag-red)',
                            }}
                            onClick={() => setDeleteModalOpen(true)}
                        >
                            Supprimer mon compte
                        </button>
                    </div>
                </div>
            </div>

            {deleteModalOpen && (
                <div
                    className="modal-backdrop"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Supprimer mon compte"
                    onClick={(event) => {
                        if (event.target === event.currentTarget) {
                            setDeleteModalOpen(false);
                        }
                    }}
                >
                    <div className="modal-panel">
                        <button
                            type="button"
                            className="modal-close"
                            onClick={() => setDeleteModalOpen(false)}
                            aria-label="Fermer"
                        >
                            <X size={16} />
                        </button>

                        <h2 style={{ fontSize: 18, color: 'var(--dag-red)' }}>
                            Supprimer définitivement votre compte ?
                        </h2>
                        <p
                            style={{
                                marginTop: 8,
                                marginBottom: 20,
                                fontSize: 13.5,
                                color: 'var(--dag-text-muted)',
                            }}
                        >
                            Cette action est irréversible. Confirmez avec votre
                            mot de passe pour continuer.
                        </p>

                        <Form
                            {...ProfileController.destroy.form()}
                            options={{ preserveScroll: true }}
                            onError={() => deletePasswordInput.current?.focus()}
                            resetOnSuccess
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 16,
                            }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div>
                                        <label htmlFor="delete-password">
                                            Mot de passe
                                        </label>
                                        <input
                                            id="delete-password"
                                            ref={deletePasswordInput}
                                            type="password"
                                            name="password"
                                            autoComplete="current-password"
                                        />
                                        {errors.password && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12,
                                                    marginTop: 4,
                                                }}
                                            >
                                                {errors.password}
                                            </p>
                                        )}
                                    </div>
                                    <button
                                        type="submit"
                                        className="btn-block"
                                        style={{
                                            background: 'var(--dag-red)',
                                            color: '#fff',
                                            border: 'none',
                                            borderRadius:
                                                'var(--dag-radius-sm)',
                                            padding: '12px 18px',
                                            fontWeight: 700,
                                            cursor: 'pointer',
                                        }}
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Suppression…'
                                            : 'Supprimer définitivement mon compte'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            )}
        </>
    );
}
