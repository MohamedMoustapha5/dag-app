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
import PublicLayout from '@/layouts/public-layout';
import { contact } from '@/routes';

type Service = {
    id: string;
    nom: string;
    description: string | null;
    categorie: string;
    fournisseur: string | null;
    image: string | null;
};

type CatalogueProps = {
    services: Service[];
};

type SortOrder = 'catalogue' | 'nom-asc' | 'nom-desc';

const ALL_CATEGORIES = 'Tous les services';

const CATEGORY_ICONS: Record<string, LucideIcon> = {
    [ALL_CATEGORIES]: LayoutGrid,
    'Création & communication': Megaphone,
    'Technique & digital': Code2,
};
const DEFAULT_CATEGORY_ICON = Sparkles;

export default function Catalogue({ services }: CatalogueProps) {
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
        <PublicLayout>
            <Head title="Catalogue de services — DAG" />

            <section className="service-catalogue-intro reveal container">
                <div
                    className="service-catalogue-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/service-app-mobile.jpg')",
                        backgroundPosition: 'center 30%',
                    }}
                >
                    <div className="service-catalogue-hero-content">
                        <span className="badge">
                            {services.length} services
                        </span>
                        <h1>Le catalogue de services DAG</h1>
                        <p>
                            Tous les services sont proposés, gérés et facturés
                            par DAG. Un seul interlocuteur, du devis à la
                            livraison.
                        </p>
                        <Link
                            href={contact()}
                            className="btn-primary catalogue-hero-cta"
                        >
                            Demander un devis
                            <ArrowRight size={16} />
                        </Link>
                    </div>
                </div>
            </section>

            <section className="container">
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
            </section>

            <section className="container">
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
            </section>

            <section className="section-gap reveal container">
                <div className="catalogue-results-head">
                    <h2 style={{ fontSize: 21, color: 'var(--dag-navy)' }}>
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
                                    setSortOrder(
                                        event.target.value as SortOrder,
                                    )
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
                    <p className="service-empty-state">
                        Aucun service ne correspond à votre recherche.
                    </p>
                ) : (
                    <div
                        className="catalogue-grid reveal-stagger"
                        style={{ marginTop: 22 }}
                    >
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
                                        href={contact()}
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
            </section>
        </PublicLayout>
    );
}
