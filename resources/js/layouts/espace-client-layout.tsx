import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Briefcase,
    FileSignature,
    FileText,
    FolderOpen,
    Headphones,
    Home,
    LayoutDashboard,
    Menu,
    Receipt,
    Search,
    Settings,
    Shield,
    UserCheck,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useRef, useState } from 'react';
import { contact, logout } from '@/routes';
import espaceClient from '@/routes/espace-client';
import candidatures from '@/routes/espace-client/candidatures';
import demandes from '@/routes/espace-client/demandes';
import offres from '@/routes/espace-client/offres';
import profilsProposes from '@/routes/espace-client/profils-proposes';

type NavItem = {
    label: string;
    icon: typeof LayoutDashboard;
    href: ReturnType<typeof espaceClient.dashboard>;
};

type NavGroup = {
    label?: string;
    items: NavItem[];
};

const NAV_PARTICULIER: NavGroup[] = [
    {
        items: [
            {
                label: 'Tableau de bord',
                icon: LayoutDashboard,
                href: espaceClient.dashboard(),
            },
            {
                label: 'Services',
                icon: Briefcase,
                href: espaceClient.services(),
            },
            { label: 'Mes demandes', icon: FileText, href: demandes.index() },
            {
                label: 'Abonnements',
                icon: Shield,
                href: espaceClient.abonnements(),
            },
            { label: 'Factures', icon: Receipt, href: espaceClient.factures() },
            {
                label: 'Contrats',
                icon: FileSignature,
                href: espaceClient.contrats(),
            },
            {
                label: 'Documents',
                icon: FolderOpen,
                href: espaceClient.documents(),
            },
            {
                label: 'Paramètres',
                icon: Settings,
                href: espaceClient.parametres(),
            },
        ],
    },
];

function navEntreprise(estPartenaireValide: boolean): NavGroup[] {
    const groups: NavGroup[] = [];

    if (estPartenaireValide) {
        groups.push({
            label: 'Recrutement',
            items: [
                {
                    label: 'Tableau de bord',
                    icon: LayoutDashboard,
                    href: espaceClient.dashboard(),
                },
                { label: 'Mes offres', icon: Home, href: offres.index() },
                {
                    label: 'Candidatures',
                    icon: Users,
                    href: candidatures.index(),
                },
                {
                    label: 'Profils proposés par DAG',
                    icon: UserCheck,
                    href: profilsProposes.index(),
                },
            ],
        });
    }

    groups.push({
        label: estPartenaireValide ? 'Services DAG' : undefined,
        items: [
            ...(estPartenaireValide
                ? []
                : [
                      {
                          label: 'Tableau de bord',
                          icon: LayoutDashboard,
                          href: espaceClient.dashboard(),
                      },
                  ]),
            {
                label: 'Services DAG',
                icon: Briefcase,
                href: espaceClient.services(),
            },
            { label: 'Mes demandes', icon: FileText, href: demandes.index() },
            {
                label: 'Conventions & contrats',
                icon: FileSignature,
                href: espaceClient.contrats(),
            },
        ],
    });

    groups.push({
        label: 'Compte & facturation',
        items: [
            {
                label: 'Mes abonnements',
                icon: Shield,
                href: espaceClient.abonnements(),
            },
            { label: 'Factures', icon: Receipt, href: espaceClient.factures() },
            {
                label: 'Mes paiements',
                icon: Wallet,
                href: espaceClient.paiements(),
            },
            {
                label: 'Mes documents',
                icon: FolderOpen,
                href: espaceClient.documents(),
            },
            {
                label: 'Profil / Paramètres',
                icon: Settings,
                href: espaceClient.parametres(),
            },
        ],
    });

    return groups;
}

export type EspaceClientInfo = {
    nom: string;
    typeLabel: string;
    type: string;
    estPartenaireValide: boolean;
};

export default function EspaceClientLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{ client: EspaceClientInfo }>();
    const { client } = props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const sidebarRef = useRef<HTMLDivElement>(null);
    const [scrollThumb, setScrollThumb] = useState<{
        height: number;
        top: number;
    } | null>(null);

    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    const initiales = client.nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');

    // Une Organisation a toujours accès au même espace qu'une Entreprise
    // (recrutement, conventions...) : les deux types partagent cette nav.
    const navGroups =
        client.type === 'ENTREPRISE' || client.type === 'ORGANISATION'
            ? navEntreprise(client.estPartenaireValide)
            : NAV_PARTICULIER;

    const updateScrollThumb = () => {
        const el = sidebarRef.current;
        if (!el) {
            return;
        }
        const { scrollTop, scrollHeight, clientHeight } = el;
        if (scrollHeight <= clientHeight + 1) {
            setScrollThumb(null);
            return;
        }
        const heightPct = Math.max((clientHeight / scrollHeight) * 100, 8);
        const maxScroll = scrollHeight - clientHeight;
        const topPct =
            maxScroll > 0 ? (scrollTop / maxScroll) * (100 - heightPct) : 0;
        setScrollThumb({ height: heightPct, top: topPct });
    };

    useEffect(() => {
        updateScrollThumb();
        window.addEventListener('resize', updateScrollThumb);
        return () => window.removeEventListener('resize', updateScrollThumb);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [navGroups.length, sidebarOpen]);

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
                    <div
                        ref={sidebarRef}
                        className="espace-sidebar-scroll"
                        onScroll={updateScrollThumb}
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
                            {navGroups.map((group, groupIndex) => (
                                <div
                                    className="espace-nav-group"
                                    key={group.label ?? `groupe-${groupIndex}`}
                                >
                                    {group.label && (
                                        <p className="espace-nav-group-label">
                                            {group.label}
                                        </p>
                                    )}
                                    {group.items.map((item) => {
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
                                </div>
                            ))}
                        </nav>

                        <div className="espace-sidebar-support">
                            <span className="espace-sidebar-support-icon">
                                <Headphones size={18} />
                            </span>
                            <strong>Besoin d'aide ?</strong>
                            <p>
                                Notre équipe est disponible pour vous
                                accompagner.
                            </p>
                            <Link href={contact()}>Contacter le support →</Link>
                        </div>
                    </div>

                    {scrollThumb && (
                        <span
                            className="espace-sidebar-scroll-thumb"
                            style={{
                                height: `${scrollThumb.height}%`,
                                top: `${scrollThumb.top}%`,
                            }}
                        />
                    )}
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
                            <input
                                type="text"
                                placeholder="Rechercher un service, une formation, une demande..."
                            />
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
                                        {client.typeLabel}
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
