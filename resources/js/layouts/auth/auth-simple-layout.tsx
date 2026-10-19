import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="dag">
            <div className="auth-shell auth-shell-photo">
                <div className="auth-card">
                    <Link
                        href={home()}
                        style={{
                            display: 'flex',
                            justifyContent: 'center',
                            marginBottom: 24,
                        }}
                    >
                        <img
                            src="/images/brand/dag-logo.jpg"
                            alt="DAG — Digital Agency Group"
                            style={{ height: 56, width: 'auto' }}
                        />
                    </Link>

                    {(title || description) && (
                        <div style={{ textAlign: 'center', marginBottom: 28 }}>
                            {title && (
                                <h1
                                    style={{
                                        fontSize: 21,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    {title}
                                </h1>
                            )}
                            {description && (
                                <p
                                    style={{
                                        marginTop: 8,
                                        fontSize: 13.5,
                                        lineHeight: 1.6,
                                        color: 'var(--dag-text-muted)',
                                    }}
                                >
                                    {description}
                                </p>
                            )}
                        </div>
                    )}

                    {children}

                    <p
                        style={{
                            textAlign: 'center',
                            marginTop: 24,
                            fontSize: 12.5,
                        }}
                    >
                        <Link href={home()} className="link-action">
                            ← Retour à l'accueil
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
