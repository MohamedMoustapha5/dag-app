import { Form, Head, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import profilRoutes from '@/routes/espace-apprenant/profil';
import { update as updatePassword } from '@/routes/user-password';

type Props = {
    apprenant: EspaceApprenantInfo;
    profil: {
        nom: string;
        email: string;
    };
};

export default function ApprenantProfil({ apprenant, profil }: Props) {
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const infosForm = useForm({
        nom: profil.nom,
        email: profil.email,
    });

    const submitInfos = (event: React.FormEvent) => {
        event.preventDefault();
        infosForm.patch(profilRoutes.update().url);
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
                            Espace apprenant DAG
                        </span>
                        <h1>{apprenant.nom}</h1>
                        <p className="account-banner-sub">
                            {apprenant.typeLabel}
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid-3">
                <div
                    className="card"
                    style={{ padding: 28, gridColumn: 'span 2' }}
                >
                    <h2 style={{ fontSize: 15, marginBottom: 20 }}>
                        Informations personnelles
                    </h2>
                    <form
                        onSubmit={submitInfos}
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                            marginBottom: 32,
                        }}
                    >
                        <div>
                            <label htmlFor="nom">Nom complet</label>
                            <input
                                id="nom"
                                type="text"
                                value={infosForm.data.nom}
                                onChange={(event) =>
                                    infosForm.setData('nom', event.target.value)
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
                            <label htmlFor="email">Adresse e-mail</label>
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

                    <h2 style={{ fontSize: 15, marginBottom: 20 }}>Sécurité</h2>
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
                        {({ errors, processing, recentlySuccessful }) => (
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
                                            {errors.password_confirmation}
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
                </div>

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
            </div>
        </>
    );
}
