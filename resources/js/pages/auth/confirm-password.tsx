import { Form, Head } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/passkey-verify';
import AuthPasswordField from '@/components/public/auth-password-field';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Confirmer le mot de passe" />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label="Confirmer avec une clé d'accès"
                loadingLabel="Confirmation..."
                separator="Ou confirmez avec votre mot de passe"
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        <div className="auth-field">
                            <label htmlFor="password">Mot de passe</label>
                            <AuthPasswordField
                                id="password"
                                name="password"
                                placeholder="Votre mot de passe"
                                autoComplete="current-password"
                                autoFocus
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
                            className="btn-primary btn-block"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing
                                ? 'Confirmation…'
                                : 'Confirmer le mot de passe'}
                        </button>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirmez votre mot de passe',
    description:
        'Ceci est une zone sécurisée. Confirmez votre mot de passe avant de continuer.',
};
