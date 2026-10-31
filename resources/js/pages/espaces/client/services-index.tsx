import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronDown,
    Code2,
    LayoutGrid,
    Megaphone,
    RotateCcw,
    Search,
    Sparkles,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import demandes from '@/routes/espace-client/demandes';

type Service = {
    id: string;
    nom: string;
    description: string | null;
    categorie: string;
    image: string | null;
};

type Props = {
    services: Service[];
};

type SortOrder = 'catalogue' | 'nom-asc' | 'nom-desc';

const ALL_CATEGORIES = 'Toutes les catégories';

const CATEGORY_ICONS: Record<string, LucideIcon> = {
    [ALL_CATEGORIES]: LayoutGrid,
    'Création & communication': Megaphone,
    'Technique & digital': Code2,
};
const DEFAULT_CATEGORY_ICON = Sparkles;

export default function ServicesIndex({ services }: Props) {
    const [search, setSearch] = useState('');
    const [activeCategory, setActiveCategory] =
        useState<string>(ALL_CATEGORIES);
    const [sortOrder, setSortOrder] = useState<SortOrder>('catalogue');

    const categories = useMemo(() => {
        const unique = Array.from(
            new Set(services.map((service) => service.categorie)),
        );
        return [ALL_CATEGORIES, ...unique];
    }, [services]);

    const filteredServices = useMemo(() => {
        return services.filter((service) => {
            const matchesCategory =
                activeCategory === ALL_CATEGORIES ||
                service.categorie === activeCategory;
            const matchesSearch =
                search.trim() === '' ||
                service.nom.toLowerCase().includes(search.toLowerCase()) ||
                (service.description ?? '')
                    .toLowerCase()
                    .includes(search.toLowerCase());
            return matchesCategory && matchesSearch;
        });
    }, [services, activeCategory, search]);

    const sortedServices = useMemo(() => {
        const list = [...filteredServices];
        if (sortOrder === 'nom-asc') {
            list.sort((a, b) => a.nom.localeCompare(b.nom));
        } else if (sortOrder === 'nom-desc') {
            list.sort((a, b) => b.nom.localeCompare(a.nom));
        }
        return list;
    }, [filteredServices, sortOrder]);

    const hasActiveFilters =
        search.trim() !== '' ||
        activeCategory !== ALL_CATEGORIES ||
        sortOrder !== 'catalogue';

    const resetFilters = () => {
        setSearch('');
        setActiveCategory(ALL_CATEGORIES);
        setSortOrder('catalogue');
    };

    return (
        <>
            <Head title="Services" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/service-app-mobile.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Services</h1>
                        <p>
                            Tout le catalogue DAG, accessible sans quitter votre
                            espace client.
                        </p>
                    </div>
                    <span className="badge badge-dark">
                        {services.length} services
                    </span>
                </div>
            </div>

            <div className="catalogue-search-bar">
                <Search size={18} />
                <input
                    type="text"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Rechercher un service, une catégorie..."
                    aria-label="Rechercher un service"
                />
                {search && (
                    <button
                        type="button"
                        className="catalogue-search-clear"
                        onClick={() => setSearch('')}
                        aria-label="Effacer la recherche"
                    >
                        <X size={14} />
                    </button>
                )}
            </div>

            <nav
                className="catalogue-category-row"
                aria-label="Filtrer par catégorie"
            >
                {categories.map((category) => {
                    const Icon =
                        CATEGORY_ICONS[category] ?? DEFAULT_CATEGORY_ICON;

                    return (
                        <button
                            key={category}
                            type="button"
                            className={`catalogue-category-btn${activeCategory === category ? ' active' : ''}`}
                            onClick={() => setActiveCategory(category)}
                        >
                            <span className="catalogue-category-icon">
                                <Icon size={20} />
                            </span>
                            <span className="catalogue-category-label">
                                {category === ALL_CATEGORIES
                                    ? 'Tous'
                                    : category}
                            </span>
                        </button>
                    );
                })}
            </nav>

            <div className="catalogue-results-head" style={{ marginTop: 28 }}>
                <h2 style={{ fontSize: 17, color: 'var(--dag-navy)' }}>
                    {activeCategory === ALL_CATEGORIES
                        ? 'Services populaires'
                        : activeCategory}
                </h2>
                <div className="catalogue-results-controls">
                    <span className="muted" style={{ fontSize: 13 }}>
                        {sortedServices.length} sur {services.length}
                    </span>
                    <div className="select-icon-wrap">
                        <select
                            id="tri"
                            aria-label="Trier par"
                            value={sortOrder}
                            onChange={(event) =>
                                setSortOrder(event.target.value as SortOrder)
                            }
                        >
                            <option value="catalogue">
                                Ordre du catalogue
                            </option>
                            <option value="nom-asc">Nom (A → Z)</option>
                            <option value="nom-desc">Nom (Z → A)</option>
                        </select>
                        <ChevronDown
                            size={16}
                            className="select-icon-chevron"
                        />
                    </div>
                    {hasActiveFilters && (
                        <button
                            type="button"
                            className="link-action service-filter-reset"
                            onClick={resetFilters}
                        >
                            <RotateCcw size={12} />
                            Réinitialiser
                        </button>
                    )}
                </div>
            </div>

            {sortedServices.length === 0 ? (
                <p className="data-table-empty">
                    Aucun service ne correspond à votre recherche.
                </p>
            ) : (
                <div className="catalogue-grid" style={{ marginTop: 22 }}>
                    {sortedServices.map((service) => (
                        <div key={service.id} className="card photo-card">
                            <div className="photo-card-media">
                                <div
                                    className="photo-card-img"
                                    style={{
                                        backgroundImage: service.image
                                            ? `url('${service.image}')`
                                            : "url('/images/photos/office-glass-dubai.jpg')",
                                    }}
                                >
                                    <span
                                        className="chip photo-card-badge"
                                        style={{
                                            background: 'var(--dag-navy)',
                                            color: '#fff',
                                        }}
                                    >
                                        DAG
                                    </span>
                                </div>
                            </div>
                            <div className="photo-card-body">
                                <h3
                                    style={{
                                        fontSize: 16,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    {service.nom}
                                </h3>
                                <span
                                    className="muted"
                                    style={{ fontSize: 12.5 }}
                                >
                                    {service.categorie}
                                </span>
                                {service.description && (
                                    <p
                                        className="muted"
                                        style={{
                                            marginTop: 8,
                                            fontSize: 13,
                                            flex: 1,
                                        }}
                                    >
                                        {service.description}
                                    </p>
                                )}
                                <Link
                                    href={demandes.create({
                                        query: { service: service.id },
                                    })}
                                    className="catalogue-card-footer"
                                >
                                    Demander ce service
                                    <span className="catalogue-card-cta">
                                        <ArrowRight size={16} />
                                    </span>
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
