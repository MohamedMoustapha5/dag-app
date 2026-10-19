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
    contact,
    formations,
    home,
    offresEmploi,
} from '@/routes';
import espaceApprenant from '@/routes/espace-apprenant';
import espaceClient from '@/routes/espace-client';

const SOCIALS = [
    { key: 'facebook', icon: Facebook },
    { key: 'instagram', icon: Instagram },
    { key: 'avis', icon: MessageCircle },
    { key: 'tiktok', icon: Music2 },
    { key: 'linkedin', icon: Linkedin },
] as const;

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
                                alt="DAG — Digital Agency Group"
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
                            DAG — Digital Agency Group. Douala, Cameroun.
                        </p>
                        <div
                            style={{ display: 'flex', gap: 10, marginTop: 20 }}
                        >
                            {SOCIALS.map((social) => (
                                <span
                                    key={social.key}
                                    className="social-icon social-icon-placeholder"
                                    aria-hidden="true"
                                >
                                    <social.icon size={16} color="#fff" />
                                </span>
                            ))}
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
                                href="https://wa.me/237699000000"
                                target="_blank"
                                rel="noreferrer"
                                className="navlink"
                            >
                                WhatsApp
                            </a>
                            <a href="mailto:contact@dag.cm" className="navlink">
                                contact@dag.cm
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <div className="site-footer-bottom">
                <span>
                    © {new Date().getFullYear()} DAG — Digital Agency Group ·
                    Douala, Cameroun
                </span>
                <div className="site-footer-legal">
                    <Link href={contact()}>Mentions légales</Link>
                    <Link href={contact()}>Confidentialité</Link>
                    <Link href={contact()}>CGU</Link>
                    <Link href={contact()}>CGV</Link>
                </div>
            </div>
        </footer>
    );
}
