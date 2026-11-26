import { Link } from '@inertiajs/react';
import {
    Facebook,
    Instagram,
    Linkedin,
    MessageCircle,
    Music2,
} from 'lucide-react';
import {
    abonnement,
    aPropos,
    catalogue,
    conditionsUtilisation,
    conditionsVente,
    contact,
    formations,
    home,
    mentionsLegales,
    offresEmploi,
    politiqueConfidentialite,
} from '@/routes';
import espaceApprenant from '@/routes/espace-apprenant';
import espaceClient from '@/routes/espace-client';

/**
 * DAG's social accounts. An entry without `href` has no account yet: it
 * stays a plain icon until its link is known.
 */
const SOCIALS: {
    key: string;
    icon: typeof Facebook;
    label: string;
    href?: string;
}[] = [
    {
        key: 'facebook',
        icon: Facebook,
        label: 'Digital Agency Group sur Facebook',
        href: 'https://www.facebook.com/share/1JeFmbk479/',
    },
    { key: 'instagram', icon: Instagram, label: 'Instagram' },
    {
        key: 'whatsapp',
        icon: MessageCircle,
        label: 'Écrire à Digital Agency Group sur WhatsApp',
        href: 'https://wa.me/237698572893',
    },
    {
        key: 'tiktok',
        icon: Music2,
        label: 'Digital Agency Group sur TikTok',
        href: 'https://www.tiktok.com/@digitalagency61',
    },
    { key: 'linkedin', icon: Linkedin, label: 'LinkedIn' },
];

export default function SiteFooter() {
    return (
        <footer className="site-footer">
            <div
                className="container"
                style={{ paddingTop: 56, paddingBottom: 32 }}
            >
                <div className="footer-grid">
                    <div className="footer-col-logo">
                        <Link href={home()} className="topbar-logo">
                            <img
                                src="/images/brand/dag-logo.jpg"
                                alt="Digital Agency Group"
                                style={{ height: 72 }}
                            />
                        </Link>
                        <p
                            className="muted"
                            style={{
                                marginTop: 16,
                                fontSize: 13.5,
                                lineHeight: 1.7,
                                maxWidth: 320,
                            }}
                        >
                            Digital Agency Group. Douala, Cameroun.
                        </p>
                        <div
                            style={{ display: 'flex', gap: 10, marginTop: 20 }}
                        >
                            {SOCIALS.map((social) =>
                                social.href ? (
                                    <a
                                        key={social.key}
                                        href={social.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="social-icon"
                                        aria-label={social.label}
                                        title={social.label}
                                    >
                                        <social.icon size={16} color="#fff" />
                                    </a>
                                ) : (
                                    <span
                                        key={social.key}
                                        className="social-icon social-icon-placeholder"
                                        aria-hidden="true"
                                    >
                                        <social.icon size={16} color="#fff" />
                                    </span>
                                ),
                            )}
                        </div>
                    </div>

                    <div className="footer-col-services">
                        <h3 style={{ fontSize: 13, color: 'var(--dag-navy)' }}>
                            Services
                        </h3>
                        <nav
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 10,
                                marginTop: 16,
                            }}
                        >
                            <Link href={catalogue()} className="navlink">
                                Catalogue
                            </Link>
                            <Link href={formations()} className="navlink">
                                Formations
                            </Link>
                            <Link href={abonnement()} className="navlink">
                                Formules
                            </Link>
                        </nav>
                    </div>

                    <div className="footer-col-espaces">
                        <h3 style={{ fontSize: 13, color: 'var(--dag-navy)' }}>
                            Espaces
                        </h3>
                        <nav
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 10,
                                marginTop: 16,
                            }}
                        >
                            <Link
                                href={espaceClient.dashboard()}
                                className="navlink"
                            >
                                Espace client
                            </Link>
                            <Link
                                href={espaceApprenant.dashboard()}
                                className="navlink"
                            >
                                Espace apprenant
                            </Link>
                            <Link href={offresEmploi()} className="navlink">
                                Offres de stage & emploi
                            </Link>
                            <Link href={contact()} className="navlink">
                                Entreprise partenaire
                            </Link>
                            <Link href={aPropos()} className="navlink">
                                Équipe DAG
                            </Link>
                        </nav>
                    </div>

                    <div className="footer-col-contact">
                        <h3 style={{ fontSize: 13, color: 'var(--dag-navy)' }}>
                            Contact
                        </h3>
                        <nav
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 10,
                                marginTop: 16,
                            }}
                        >
                            <Link href={contact()} className="navlink">
                                Nous contacter
                            </Link>
                            <Link href={aPropos()} className="navlink">
                                À propos
                            </Link>
                            <a
                                href="https://wa.me/237698572893"
                                target="_blank"
                                rel="noreferrer"
                                className="navlink"
                            >
                                WhatsApp
                            </a>
                            <a
                                href="mailto:contact@agencydag.com"
                                className="navlink"
                            >
                                contact@agencydag.com
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <div className="site-footer-bottom">
                <span>
                    © {new Date().getFullYear()} Digital Agency Group · Douala,
                    Cameroun
                </span>
                <div className="site-footer-legal">
                    <Link href={mentionsLegales()}>Mentions légales</Link>
                    <Link href={politiqueConfidentialite()}>
                        Confidentialité
                    </Link>
                    <Link href={conditionsUtilisation()}>CGU</Link>
                    <Link href={conditionsVente()}>CGV</Link>
                </div>
            </div>
        </footer>
    );
}
