import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    FileText,
    Folder,
    Headphones,
    LayoutDashboard,
    Menu,
    Receipt,
    TrendingUp,
    User,
    X,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useRef, useState } from 'react';
import { contact, logout } from '@/routes';
import espaceApprenant from '@/routes/espace-apprenant';

type NavItem = {
    label: string;
    icon: typeof LayoutDashboard;
    href: ReturnType<typeof espaceApprenant.dashboard> | null;
};

const NAV_ITEMS: NavItem[] = [
    {
        label: 'Tableau de bord',
        icon: LayoutDashboard,
        href: espaceApprenant.dashboard(),
    },
    {
        label: 'Formations',
        icon: BookOpen,
        href: espaceApprenant.formations(),
    },
    {
        label: 'Mes inscriptions',
        icon: FileText,
        href: espaceApprenant.inscriptions(),
    },
    {
        label: 'Factures',
        icon: Receipt,
        href: espaceApprenant.factures(),
    },
    {
        label: 'Mon dossier',
        icon: Folder,
        href: espaceApprenant.dossier(),
    },
    {
        label: 'Progression',
        icon: TrendingUp,
        href: espaceApprenant.progression(),
    },
    {
        label: 'Profil',
        icon: User,
        href: espaceApprenant.profil(),
    },
];

export type EspaceApprenantInfo = {
    nom: string;
    typeLabel: string;
};

export default function EspaceApprenantLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{ apprenant: EspaceApprenantInfo }>();
    const { apprenant } = props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const sidebarRef = useRef<HTMLDivElement>(null);
    const [scrollThumb, setScrollThumb] = useState<{
        height: number;
        top: number;
    } | null>(null);

    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    const initiales = apprenant.nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');

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
    }, [sidebarOpen]);

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
                            href={espaceApprenant.dashboard()}
                            className="espace-sidebar-logo"
                        >
                            <img
                                src="/images/brand/dag-logo.jpg"
                                alt="DAG — Digital Agency Group"
                            />
                        </Link>

                        <nav className="espace-nav">
                            <div className="espace-nav-group">
                                {NAV_ITEMS.map((item) =>
                                    item.href ? (
                                        <Link
                                            key={item.label}
                                            href={item.href}
                                            className={`espace-nav-link${url === item.href.url ? ' active' : ''}`}
                                        >
                                            <item.icon size={18} />
                                            {item.label}
                                        </Link>
                                    ) : (
                                        <span
                                            key={item.label}
                                            className="espace-nav-link inert"
                                            title="Disponible prochainement"
                                        >
                                            <item.icon size={18} />
                                            {item.label}
                                        </span>
                                    ),
                                )}
                            </div>
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

                        <img
                            src="/images/brand/dag-logo.jpg"
                            alt="DAG — Digital Agency Group"
                            className="espace-topbar-logo"
                        />

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
                                        {apprenant.nom}
                                    </p>
                                    <p className="espace-user-type">
                                        {apprenant.typeLabel}
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
