import { Head, Link, useForm } from '@inertiajs/react';
import AuthPasswordField from '@/components/public/auth-password-field';
import { login } from '@/routes';

type Props = {
    entreprise: string;
    nom: string;
    email: string;
    invitePar: string | null;
    dejaActive: boolean;
    passwordRules: string;
    action: string;
};

function Erreur({ message }: { message?: string }) {
    return message ? (
        <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
            {message}
        </p>
    ) : null;
}

export default function InvitationCollaborateur({
    entreprise,
    nom,
    email,
    invitePar,
    dejaActive,
    passwordRules,
    action,
}: Props) {
    const form = useForm({ password: '', password_confirmation: '' });

    const activer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(action, {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    if (dejaActive) {
        return (
            <>
                <Head title="Accès déjà activé" />
                <p
                    style={{
                        textAlign: 'center',
                        fontSize: 13.5,
                        marginBottom: 20,
                    }}
                >
                    {`Votre accès à l'espace de ${entreprise} est déjà activé.`}
                </p>
                <Link href={login()} className="btn-primary btn-block">
                    Se connecter
                </Link>
            </>
        );
    }

    return (
        <>
            <Head title="Activer votre accès" />

            <div
                style={{
                    background: 'var(--dag-blue-light)',
                    borderRadius: 12,
                    padding: '14px 16px',
                    fontSize: 13,
                    lineHeight: 1.55,
                    marginBottom: 20,
                }}
            >
                <strong>{`Bonjour ${nom},`}</strong>
                <br />
                {invitePar
                    ? `${invitePar} vous donne accès à l'espace DAG de ${entreprise}.`
                    : `Vous avez accès à l'espace DAG de ${entreprise}.`}
            </div>

            <form
                onSubmit={activer}
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
            >
                <div className="auth-field">
                    <label htmlFor="email">E-mail</label>
                    <input
                        id="email"
                        type="email"
                        autoComplete="email"
                        value={email}
                        readOnly
                    />
                </div>

                <div className="auth-field">
                    <label htmlFor="password">Choisissez un mot de passe</label>
                    <AuthPasswordField
                        id="password"
                        name="password"
                        autoComplete="new-password"
                        autoFocus
                        placeholder="Votre mot de passe"
                        passwordrules={passwordRules}
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                    />
                    <Erreur message={form.errors.password} />
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
                        value={form.data.password_confirmation}
                        onChange={(event) =>
                            form.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                    <Erreur message={form.errors.password_confirmation} />
                </div>

                <button
                    type="submit"
                    className="btn-primary btn-block"
                    disabled={form.processing}
                >
                    {form.processing ? 'Activation…' : 'Activer mon accès'}
                </button>
            </form>
        </>
    );
}

InvitationCollaborateur.layout = {
    title: 'Activer votre accès',
    description:
        'Choisissez votre mot de passe pour gérer l’espace de votre structure.',
};
