import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    Bell,
    Briefcase,
    BookOpen,
    BarChart3,
    FileText,
    FileSignature,
    LayoutDashboard,
    LayoutGrid,
    LogOut,
    Menu,
    Receipt,
    Shield,
    Sparkles,
    Tag,
    UserRound,
    Users,
    UsersRound,
    X,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useEffect, useRef, useState } from 'react';
import { logout } from '@/routes';
import LanguageSwitcher from '@/components/public/language-switcher';
import espaceAdmin from '@/routes/espace-admin';
import abonnementsAdmin from '@/routes/espace-admin/abonnements';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';
import attestationsAdmin from '@/routes/espace-admin/attestations';
import clientsAdmin from '@/routes/espace-admin/clients';
import contratsAdmin from '@/routes/espace-admin/contrats';
import demandesAdmin from '@/routes/espace-admin/demandes';
import entreprisesAdmin from '@/routes/espace-admin/entreprises';
import equipeAdmin from '@/routes/espace-admin/equipe';
import facturesAdmin from '@/routes/espace-admin/factures';
import formationsAdmin from '@/routes/espace-admin/formations';
import formulesAdmin from '@/routes/espace-admin/formules';
import grilleTarifaireAdmin from '@/routes/espace-admin/grille-tarifaire';
import servicesAdmin from '@/routes/espace-admin/services';

type NavItem = {
    label: string;
    icon: typeof LayoutDashboard;
    href: ReturnType<typeof espaceAdmin.dashboard>;
    /** Donnee key in the droits matrix this item is gated by, or
     * 'TOUJOURS' for items every membre sees, or 'PROMOTRICE' for items
     * reserved to that role regardless of the matrix. */
    visibleIf: string;
};

type NavGroup = {
    label?: string;
    items: NavItem[];
};

const NAV_GROUPS: NavGroup[] = [
    {
        items: [
            {
                label: 'Tableau de bord',
                icon: LayoutDashboard,
                href: espaceAdmin.dashboard(),
                visibleIf: 'TOUJOURS',
            },
        ],
    },
    {
        label: 'Catalogue & formations',
        items: [
            {
                label: 'Catalogue des services',
                icon: LayoutGrid,
                href: servicesAdmin.index(),
                visibleIf: 'CATALOGUE',
            },
            {
                label: 'Grille tarifaire',
                icon: Tag,
                href: grilleTarifaireAdmin.index(),
                visibleIf: 'CATALOGUE',
            },
            {
                label: 'Formations & sessions',
                icon: BookOpen,
                href: formationsAdmin.index(),
                visibleIf: 'FORMATIONS',
            },
        ],
    },
    {
        label: 'Activité commerciale',
        items: [
            {
                label: 'Demandes clients',
                icon: FileText,
                href: demandesAdmin.index(),
                visibleIf: 'DEMANDES_CLIENTS',
            },
            {
                label: 'Comptes clients',
                icon: UsersRound,
                href: clientsAdmin.index(),
                visibleIf: 'DEMANDES_CLIENTS',
            },
            {
                label: 'Factures & paiements',
                icon: Receipt,
                href: facturesAdmin.index(),
                visibleIf: 'FACTURES_PAIEMENTS',
            },
            {
                label: 'Contrats & clauses',
                icon: FileSignature,
                href: contratsAdmin.index(),
                visibleIf: 'CONTRATS_CLAUSES',
            },
            {
                label: 'Abonnements',
                icon: Shield,
                href: abonnementsAdmin.index(),
                visibleIf: 'ABONNEMENTS',
            },
            {
                label: 'Formules d’abonnement',
                icon: Sparkles,
                href: formulesAdmin.index(),
                visibleIf: 'ABONNEMENTS',
            },
        ],
    },
    {
        label: 'Recrutement',
        items: [
            {
                label: 'Entreprises & offres',
                icon: Briefcase,
                href: entreprisesAdmin.index(),
                visibleIf: 'ENTREPRISES_OFFRES',
            },
        ],
    },
    {
        label: 'Apprenants',
        items: [
            {
                label: 'Dossiers apprenants',
                icon: UserRound,
                href: apprenantsAdmin.index(),
                visibleIf: 'DOSSIERS_APPRENANTS',
            },
            {
                label: 'Attestations',
                icon: Award,
                href: attestationsAdmin.index(),
                visibleIf: 'DOSSIERS_APPRENANTS',
            },
        ],
    },
    {
        label: 'Pilotage',
        items: [
            {
                label: 'Statistiques',
                icon: BarChart3,
                href: espaceAdmin.statistiques(),
                visibleIf: 'STATISTIQUES',
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Équipe DAG',
                icon: Users,
                href: equipeAdmin.index(),
                visibleIf: 'PROMOTRICE',
            },
        ],
    },
];

export type EspaceAdminInfo = {
    role: string;
    roleLabel: string;
};

export default function EspaceAdminLayout({ children }: PropsWithChildren) {
    const { url, props } = usePage<{
        membre: EspaceAdminInfo;
        droits: Record<string, string>;
    }>();
    const { membre, droits } = props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const sidebarRef = useRef<HTMLDivElement>(null);
    const [scrollThumb, setScrollThumb] = useState<{
        height: number;
        top: number;
    } | null>(null);

    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    const peutVoir = (visibleIf: string): boolean => {
        if (visibleIf === 'TOUJOURS') {
            return true;
        }
        if (visibleIf === 'PROMOTRICE') {
            return membre.role === 'PROMOTRICE';
        }
        return droits[visibleIf] !== 'AUCUN';
    };

    const navGroups = NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter((item) => peutVoir(item.visibleIf)),
    })).filter((group) => group.items.length > 0);

    const initiales = membre.roleLabel
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
                            href={espaceAdmin.dashboard()}
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
                            <LanguageSwitcher />
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
                                        Équipe DAG
                                    </p>
                                    <p className="espace-user-type">
                                        {membre.roleLabel}
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={logout()}
                                method="post"
                                as="button"
                                className="btn-ghost"
                                aria-label="Déconnexion"
                            >
                                <span className="btn-label-full">
                                    Déconnexion
                                </span>
                                <span className="btn-label-short">
                                    <LogOut size={16} />
                                </span>
                            </Link>
                        </div>
                    </header>

                    <main className="espace-content">{children}</main>
                </div>
            </div>
        </div>
    );
}
