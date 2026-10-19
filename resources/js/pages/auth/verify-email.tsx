import { Form, Head, Link } from '@inertiajs/react';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Vérification de l'e-mail" />

            {status === 'verification-link-sent' && (
                <p
                    style={{
                        marginBottom: 16,
                        textAlign: 'center',
                        fontSize: 13,
                        fontWeight: 600,
                        color: 'var(--dag-green)',
                    }}
                >
                    Un nouveau lien de vérification a été envoyé à l'adresse
                    e-mail fournie lors de votre inscription.
                </p>
            )}

            <Form
                {...send.form()}
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 16,
                    textAlign: 'center',
                }}
            >
                {({ processing }) => (
                    <>
                        <button
                            type="submit"
                            className="btn-outline btn-block"
                            disabled={processing}
                        >
                            {processing
                                ? 'Envoi…'
                                : "Renvoyer l'e-mail de vérification"}
                        </button>

                        <Link
                            href={logout()}
                            className="link-action"
                            style={{ margin: '0 auto' }}
                        >
                            Se déconnecter
                        </Link>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Vérifiez votre adresse e-mail',
    description:
        'Merci de confirmer votre e-mail en cliquant sur le lien que nous venons de vous envoyer.',
};
