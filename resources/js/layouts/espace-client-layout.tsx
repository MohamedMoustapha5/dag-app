import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Briefcase,
    FileSignature,
    FileText,
    FolderOpen,
    Headphones,
    LayoutDashboard,
    Menu,
    Receipt,
    Search,
    Settings,
    Shield,
    X,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useState } from 'react';
import { contact, logout } from '@/routes';
import espaceClient from '@/routes/espace-client';
import demandes from '@/routes/espace-client/demandes';

type NavItem = {
    label: string;
    icon: typeof LayoutDashboard;
    href: ReturnType<typeof espaceClient.dashboard>;
};

const NAV_ITEMS: NavItem[] = [
    {
        label: 'Tableau de bord',
        icon: LayoutDashboard,
        href: espaceClient.dashboard(),
    },
    { label: 'Services', icon: Briefcase, href: espaceClient.services() },
    { label: 'Mes demandes', icon: FileText, href: demandes.index() },
    { label: 'Abonnements', icon: Shield, href: espaceClient.abonnements() },
    { label: 'Factures', icon: Receipt, href: espaceClient.factures() },
    { label: 'Contrats', icon: FileSignature, href: espaceClient.contrats() },
    { label: 'Documents', icon: FolderOpen, href: espaceClient.documents() },
    { label: 'Paramètres', icon: Settings, href: espaceClient.parametres() },
];

export type EspaceClientInfo = { nom: string; typeLabel: string };

type Props = PropsWithChildren<{
    client: EspaceClientInfo;
}>;

export default function EspaceClientLayout({ children, client }: Props) {
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    const initiales = client.nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');

    return (
        <div className="dag">
            <div className="espace-shell">
                <button
                    type="button"
                    className={`espace-sidebar-backdrop${sidebarOpen ? ' open' : ''}`}
                    aria-label="Fermer le menu"
                    onClick={() => setSidebarOpen(false)}
                />

                <aside
                    className={`espace-sidebar${sidebarOpen ? ' open' : ''}`}
                >
                    <Link
                        href={espaceClient.dashboard()}
                        className="espace-sidebar-logo"
                    >
                        <img
                            src="/images/brand/dag-logo.jpg"
                            alt="DAG — Digital Agency Group"
                        />
                    </Link>

                    <nav className="espace-nav">
                        {NAV_ITEMS.map((item) => {
                            const isActive = url === item.href.url;

                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={`espace-nav-link${isActive ? ' active' : ''}`}
                                >
                                    <item.icon size={18} />
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="espace-sidebar-support">
                        <span className="espace-sidebar-support-icon">
                            <Headphones size={18} />
                        </span>
                        <strong>Besoin d'aide ?</strong>
                        <p>
                            Notre équipe est disponible pour vous accompagner.
                        </p>
                        <Link href={contact()}>Contacter le support →</Link>
                    </div>
                </aside>

                <div className="espace-main">
                    <header className="espace-topbar">
                        <button
                            type="button"
                            className="espace-sidebar-toggle"
                            aria-label="Ouvrir le menu"
                            onClick={() => setSidebarOpen((open) => !open)}
                        >
                            {sidebarOpen ? <X size={18} /> : <Menu size={18} />}
                        </button>

                        <div className="espace-search">
                            <Search size={16} />
                            <input type="text" placeholder="Rechercher..." />
                        </div>

                        <div className="espace-topbar-right">
                            <span className="espace-notif">
                                <Bell size={18} />
                                <span className="espace-notif-dot" />
                            </span>
                            <div className="espace-user">
                                <span className="espace-avatar">
                                    {initiales}
                                </span>
                                <div>
                                    <p className="espace-user-name">
                                        {client.nom}
                                    </p>
                                    <p className="espace-user-type">
                                        Client — {client.typeLabel}
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={logout()}
                                method="post"
                                as="button"
                                className="btn-ghost"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </header>

                    <main className="espace-content">{children}</main>
                </div>
            </div>
        </div>
    );
}
