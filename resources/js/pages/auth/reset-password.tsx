import { Form, Head } from '@inertiajs/react';
import AuthPasswordField from '@/components/public/auth-password-field';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="Réinitialiser le mot de passe" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        <div className="auth-field">
                            <label htmlFor="email">E-mail</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                readOnly
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
                            <label htmlFor="password">
                                Nouveau mot de passe
                            </label>
                            <AuthPasswordField
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                autoFocus
                                placeholder="Votre nouveau mot de passe"
                                passwordrules={passwordRules}
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

                        <div className="auth-field">
                            <label htmlFor="password_confirmation">
                                Confirmer le mot de passe
                            </label>
                            <AuthPasswordField
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                placeholder="Confirmez le mot de passe"
                                passwordrules={passwordRules}
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
                            className="btn-primary btn-block"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing
                                ? 'Enregistrement…'
                                : 'Réinitialiser le mot de passe'}
                        </button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Réinitialiser votre mot de passe',
    description: 'Choisissez un nouveau mot de passe ci-dessous.',
};
