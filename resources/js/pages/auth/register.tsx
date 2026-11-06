import { Form, Head, Link } from '@inertiajs/react';
import AuthPasswordField from '@/components/public/auth-password-field';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import {
    conditionsUtilisation,
    login,
    politiqueConfidentialite,
} from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

type Props = {
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
    intention?: { message: string; compteType: string } | null;
};

export default function Register({
    passwordRules,
    teamInvitation,
    intention,
}: Props) {
    return (
        <>
            <Head title="Créer un compte" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
            >
                {({ processing, errors }) => (
                    <>
                        {teamInvitation && (
                            <TeamInvitationAlert
                                invitation={teamInvitation}
                                action="Register"
                            />
                        )}

                        {intention && (
                            <div
                                role="status"
                                style={{
                                    padding: '12px 14px',
                                    borderRadius: 10,
                                    background: 'var(--dag-blue-50, #eff6ff)',
                                    border: '1px solid var(--dag-blue-200, #bfdbfe)',
                                    color: 'var(--dag-blue-900, #1e3a8a)',
                                    fontSize: 14,
                                    marginBottom: 4,
                                }}
                            >
                                {intention.message}
                            </div>
                        )}

                        <div className="auth-field">
                            <label htmlFor="compte_type">Je suis</label>
                            <select
                                id="compte_type"
                                name="compte_type"
                                required
                                tabIndex={1}
                                defaultValue={
                                    intention?.compteType ?? 'particulier'
                                }
                            >
                                <option value="particulier">Particulier</option>
                                <option value="entreprise">Entreprise</option>
                                <option value="organisation">
                                    Organisation
                                </option>
                                <option value="apprenant">
                                    Apprenant — je cherche une formation
                                </option>
                            </select>
                            {errors.compte_type && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.compte_type}
                                </p>
                            )}
                        </div>

                        <div className="auth-field">
                            <label htmlFor="name">Nom complet</label>
                            <input
                                id="name"
                                type="text"
                                required
                                tabIndex={2}
                                autoComplete="name"
                                name="name"
                                placeholder="Votre nom"
                            />
                            {errors.name && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="auth-field">
                            <label htmlFor="email">Adresse e-mail</label>
                            <input
                                id="email"
                                type="email"
                                required
                                tabIndex={3}
                                autoComplete="email"
                                name="email"
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
                            <label htmlFor="password">Mot de passe</label>
                            <AuthPasswordField
                                id="password"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                                name="password"
                                placeholder="Votre mot de passe"
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
                                required
                                tabIndex={5}
                                autoComplete="new-password"
                                name="password_confirmation"
                                placeholder="Confirmez votre mot de passe"
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

                        <label
                            htmlFor="terms"
                            style={{
                                display: 'flex',
                                alignItems: 'flex-start',
                                gap: 10,
                                fontSize: 13,
                                lineHeight: 1.6,
                                color: 'var(--dag-text)',
                                cursor: 'pointer',
                            }}
                        >
                            <input
                                type="checkbox"
                                id="terms"
                                name="terms"
                                required
                                tabIndex={6}
                                style={{ marginTop: 3 }}
                            />
                            <span>
                                J'accepte les{' '}
                                <a
                                    href={conditionsUtilisation.url()}
                                    target="_blank"
                                    rel="noopener"
                                >
                                    conditions générales d'utilisation
                                </a>{' '}
                                et reconnais avoir pris connaissance de la{' '}
                                <a
                                    href={politiqueConfidentialite.url()}
                                    target="_blank"
                                    rel="noopener"
                                >
                                    politique de confidentialité
                                </a>{' '}
                                de DAG.
                            </span>
                        </label>
                        {errors.terms && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: -10,
                                }}
                            >
                                {errors.terms}
                            </p>
                        )}

                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            tabIndex={7}
                            disabled={processing}
                            data-test="register-user-button"
                        >
                            {processing ? 'Création…' : 'Créer un compte'}
                        </button>

                        <p
                            style={{
                                textAlign: 'center',
                                fontSize: 13,
                                color: 'var(--dag-text-muted)',
                            }}
                        >
                            Déjà un compte ?{' '}
                            <Link
                                href={
                                    teamInvitation
                                        ? login.url({
                                              query: {
                                                  invitation:
                                                      teamInvitation.code,
                                              },
                                          })
                                        : login()
                                }
                                data-test="team-invitation-login-link"
                                tabIndex={8}
                            >
                                Se connecter
                            </Link>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Créer votre compte DAG',
    description: 'Renseignez vos informations pour commencer.',
};
