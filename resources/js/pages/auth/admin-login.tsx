import { Form, Head } from '@inertiajs/react';
import AuthPasswordField from '@/components/public/auth-password-field';
import { store } from '@/routes/admin/connexion';

export default function AdminLogin() {
    return (
        <>
            <Head title="Connexion — Administration" />

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
                                autoComplete="email"
                                placeholder="prenom@dag.cm"
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
                            <label htmlFor="password">Mot de passe</label>
                            <AuthPasswordField
                                id="password"
                                name="password"
                                required
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
                                cursor: 'pointer',
                            }}
                        >
                            <input type="checkbox" name="remember" />
                            Se souvenir de moi
                        </label>

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={processing}
                        >
                            {processing ? 'Connexion…' : 'Se connecter'}
                        </button>
                    </>
                )}
            </Form>
        </>
    );
}

AdminLogin.layout = {
    title: 'Espace administration DAG',
    description: "Accès réservé à l'équipe DAG.",
};
