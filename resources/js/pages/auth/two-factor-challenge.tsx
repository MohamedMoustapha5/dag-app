import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Code de récupération',
                description:
                    "Confirmez votre identité en saisissant l'un de vos codes de récupération.",
                toggleText: "se connecter avec un code d'authentification",
            };
        }

        return {
            title: "Code d'authentification",
            description:
                "Entrez le code fourni par votre application d'authentification.",
            toggleText: 'utiliser un code de récupération',
        };
    }, [showRecoveryInput]);

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    });

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title="Authentification à deux facteurs" />

            <Form
                {...store.form()}
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors }) => (
                    <>
                        {showRecoveryInput ? (
                            <div className="auth-field">
                                <input
                                    name="recovery_code"
                                    type="text"
                                    placeholder="Code de récupération"
                                    autoFocus={showRecoveryInput}
                                    required
                                />
                                {errors.recovery_code && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.recovery_code}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    alignItems: 'center',
                                    gap: 12,
                                    textAlign: 'center',
                                }}
                            >
                                <InputOTP
                                    name="code"
                                    maxLength={OTP_MAX_LENGTH}
                                    value={code}
                                    onChange={(value) => setCode(value)}
                                    disabled={processing}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    autoFocus
                                >
                                    <InputOTPGroup>
                                        {Array.from(
                                            { length: OTP_MAX_LENGTH },
                                            (_, index) => (
                                                <InputOTPSlot
                                                    key={index}
                                                    index={index}
                                                />
                                            ),
                                        )}
                                    </InputOTPGroup>
                                </InputOTP>
                                {errors.code && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                        }}
                                    >
                                        {errors.code}
                                    </p>
                                )}
                            </div>
                        )}

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={processing}
                        >
                            Continuer
                        </button>

                        <p
                            style={{
                                textAlign: 'center',
                                fontSize: 13,
                                color: 'var(--dag-text-muted)',
                            }}
                        >
                            Ou{' '}
                            <button
                                type="button"
                                className="link-action"
                                onClick={() => toggleRecoveryMode(clearErrors)}
                            >
                                {authConfigContent.toggleText}
                            </button>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}
