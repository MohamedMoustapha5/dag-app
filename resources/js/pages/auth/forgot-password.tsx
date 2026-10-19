import { Form, Head, Link } from '@inertiajs/react';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Mot de passe oublié" />

            {status && (
                <p
                    style={{
                        marginBottom: 16,
                        textAlign: 'center',
                        fontSize: 13,
                        fontWeight: 600,
                        color: 'var(--dag-green)',
                    }}
                >
                    {status}
                </p>
            )}

            <Form {...email.form()}>
                {({ processing, errors }) => (
                    <>
                        <div className="auth-field">
                            <label htmlFor="email">Adresse e-mail</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="off"
                                autoFocus
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
                            style={{ marginTop: 20 }}
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing
                                ? 'Envoi…'
                                : 'Recevoir le lien de réinitialisation'}
                        </button>
                    </>
                )}
            </Form>

            <p
                style={{
                    textAlign: 'center',
                    marginTop: 20,
                    fontSize: 13,
                    color: 'var(--dag-text-muted)',
                }}
            >
                Ou retournez à la <Link href={login()}>page de connexion</Link>
            </p>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Mot de passe oublié',
    description:
        'Entrez votre e-mail pour recevoir un lien de réinitialisation.',
};
