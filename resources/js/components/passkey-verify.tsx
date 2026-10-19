import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound } from 'lucide-react';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

export default function PasskeyVerify({
    routes,
    label,
    loadingLabel,
    separator,
}: Props = {}) {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        ...(routes && {
            routes: {
                options: routes.options.url,
                submit: routes.submit.url,
            },
        }),
        onSuccess: (response) => {
            router.visit(response.redirect ?? '/dashboard');
        },
    });

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                className="btn-outline btn-block"
                onClick={verify}
                disabled={isLoading}
            >
                <KeyRound size={16} />
                {isLoading
                    ? (loadingLabel ?? 'Authenticating...')
                    : (label ?? 'Sign in with a passkey')}
            </button>
            {error && (
                <p
                    style={{
                        marginTop: 8,
                        textAlign: 'center',
                        fontSize: 12.5,
                        color: 'var(--dag-red)',
                    }}
                >
                    {error}
                </p>
            )}

            <div
                style={{
                    position: 'relative',
                    margin: '24px 0',
                    textAlign: 'center',
                }}
            >
                <div
                    style={{
                        position: 'absolute',
                        left: 0,
                        right: 0,
                        top: '50%',
                        height: 1,
                        background: 'var(--dag-border)',
                    }}
                />
                <span
                    style={{
                        position: 'relative',
                        background: '#fff',
                        padding: '0 10px',
                        fontSize: 11,
                        fontWeight: 700,
                        textTransform: 'uppercase',
                        letterSpacing: '.04em',
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    {separator ?? 'Or continue with email'}
                </span>
            </div>
        </>
    );
}
