import { Form, Head, Link } from '@inertiajs/react';
import PasskeyVerify from '@/components/passkey-verify';
import AuthPasswordField from '@/components/public/auth-password-field';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { TeamInvitationContext } from '@/types';

type Props = {
    status?: string;
    canResetPassword: boolean;
    teamInvitation?: TeamInvitationContext | null;
};

export default function Login({
    status,
    canResetPassword,
    teamInvitation,
}: Props) {
    return (
        <>
            <Head title="Connexion" />

            {teamInvitation && (
                <TeamInvitationAlert
                    invitation={teamInvitation}
                    action="Log in"
                />
            )}

            <PasskeyVerify
                label="Se connecter avec une clé d'accès"
                loadingLabel="Authentification..."
                separator="Ou continuez avec votre e-mail"
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
            >
                {({ processing, errors }) => (
                    <>
                        <div className="auth-field">
                            <label htmlFor="email">Adresse e-mail</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
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

                        <div className="auth-field">
                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                    marginBottom: 6,
                                }}
                            >
                                <label
                                    htmlFor="password"
                                    style={{ marginBottom: 0 }}
                                >
                                    Mot de passe
                                </label>
                                {canResetPassword && (
                                    <Link
                                        href={request()}
                                        tabIndex={5}
                                        style={{ fontSize: 12.5 }}
                                    >
                                        Mot de passe oublié ?
                                    </Link>
                                )}
                            </div>
                            <AuthPasswordField
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Votre mot de passe"
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

                        <label
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 8,
                                fontSize: 13,
                                fontWeight: 500,
                                color: 'var(--dag-text)',
                                cursor: 'pointer',
                            }}
                        >
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            Se souvenir de moi
                        </label>

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            style={{ marginTop: 4 }}
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing ? 'Connexion…' : 'Se connecter'}
                        </button>

                        <p
                            style={{
                                textAlign: 'center',
                                fontSize: 13,
                                color: 'var(--dag-text-muted)',
                            }}
                        >
                            Pas encore de compte ?{' '}
                            <Link
                                href={register({
                                    query: {
                                        invitation: teamInvitation?.code,
                                    },
                                })}
                                data-test="register-link"
                                tabIndex={5}
                            >
                                Créer un compte
                            </Link>
                        </p>
                    </>
                )}
            </Form>

            {status && (
                <p
                    style={{
                        marginTop: 16,
                        textAlign: 'center',
                        fontSize: 13,
                        fontWeight: 600,
                        color: 'var(--dag-green)',
                    }}
                >
                    {status}
                </p>
            )}
        </>
    );
}

Login.layout = {
    title: 'Connexion à votre espace DAG',
    description: 'Entrez votre e-mail et votre mot de passe pour continuer.',
};
