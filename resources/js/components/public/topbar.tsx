import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    abonnement,
    aPropos,
    catalogue,
    contact,
    formations,
    home,
    login,
    register,
} from '@/routes';

const NAV_LINKS = [
    { href: home(), label: 'Accueil' },
    { href: catalogue(), label: 'Services' },
    { href: formations(), label: 'Formations' },
    { href: abonnement(), label: 'Abonnement' },
    { href: aPropos(), label: 'À propos' },
    { href: contact(), label: 'Contact' },
];

export default function TopBar() {
    const { auth, currentTeam } = usePage().props;
    const { url } = usePage();
    const [isScrolled, setIsScrolled] = useState(false);
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setIsScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        setIsMenuOpen(false);
    }, [url]);

    const dashboardUrl = currentTeam ? `/${currentTeam.slug}/dashboard` : '/';

    return (
        <header className={`topbar${isScrolled ? ' is-scrolled' : ''}`}>
            <div className="topbar-inner">
                <Link href={home()} className="topbar-logo">
                    <img
                        src="/images/brand/dag-logo.jpg"
                        alt="DAG — Digital Agency Group"
                    />
                </Link>

                <nav className="topbar-nav">
                    {NAV_LINKS.map((link) => (
                        <Link
                            key={link.label}
                            href={link.href}
                            prefetch
                            className={`navlink${url === link.href.url ? ' active' : ''}`}
                        >
                            {link.label}
                        </Link>
                    ))}
                </nav>

                <div className="topbar-actions">
                    <div className="lang-switch" aria-label="Langue">
                        <button type="button" className="lang-option active">
                            <span className="flag" aria-hidden="true">
                                🇫🇷
                            </span>
                            <span className="code">FR</span>
                        </button>
                        <button type="button" className="lang-option">
                            <span className="flag" aria-hidden="true">
                                🇬🇧
                            </span>
                            <span className="code">EN</span>
                        </button>
                    </div>

                    {auth.user ? (
                        <Link href={dashboardUrl} className="btn-ghost">
                            Mon espace
                        </Link>
                    ) : (
                        <>
                            <Link href={login()} className="btn-ghost">
                                Se connecter
                            </Link>
                            <Link href={register()} className="btn-primary">
                                <span className="btn-label-full">
                                    Créer un compte
                                </span>
                                <span className="btn-label-short">
                                    S'inscrire
                                </span>
                            </Link>
                        </>
                    )}

                    <button
                        type="button"
                        className={`menu-toggle${isMenuOpen ? ' open' : ''}`}
                        aria-label="Ouvrir le menu"
                        aria-expanded={isMenuOpen}
                        onClick={() => setIsMenuOpen((open) => !open)}
                    >
                        <svg
                            className="icon-burger"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                        >
                            <path d="M3 6h18M3 12h18M3 18h18" />
                        </svg>
                        <svg
                            className="icon-close"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                        >
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>

                    <div className={`mobile-menu${isMenuOpen ? ' open' : ''}`}>
                        {NAV_LINKS.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="navlink"
                            >
                                {link.label}
                            </Link>
                        ))}
                        <div className="mobile-menu-divider" />
                        {auth.user ? (
                            <Link
                                href={dashboardUrl}
                                className="btn-primary btn-block"
                            >
                                Mon espace
                            </Link>
                        ) : (
                            <Link
                                href={register()}
                                className="btn-primary btn-block"
                            >
                                Démarrer un projet
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}
