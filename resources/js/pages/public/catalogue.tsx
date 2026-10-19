import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronDown, RotateCcw, Search, X } from 'lucide-react';
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

    const groupedServices = useMemo(() => {
        return categories
            .filter((category) => category !== ALL_CATEGORIES)
            .map((category) => ({
                category,
                services: sortedServices.filter(
                    (service) => service.categorie === category,
                ),
            }))
            .filter((group) => group.services.length > 0);
    }, [categories, sortedServices]);

    return (
        <PublicLayout>
            <Head title="Catalogue de services — DAG" />

            <section className="service-catalogue-intro reveal container">
                <div
                    className="service-catalogue-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/team-meeting-laptop.jpg')",
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
                    </div>
                </div>
            </section>

            <section className="section-gap container">
                <div className="service-filter-toolbar">
                    <div className="service-filter-search">
                        <label htmlFor="search">Rechercher un service</label>
                        <div className="input-icon-wrap">
                            <Search size={16} className="input-icon-left" />
                            <input
                                id="search"
                                type="text"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Ex. community management..."
                                style={
                                    search ? { paddingRight: 36 } : undefined
                                }
                            />
                            {search && (
                                <button
                                    type="button"
                                    className="input-icon-clear"
                                    onClick={() => setSearch('')}
                                    aria-label="Effacer la recherche"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                    </div>
                    <div>
                        <label htmlFor="categorie">Catégorie</label>
                        <div className="select-icon-wrap">
                            <select
                                id="categorie"
                                value={activeCategory}
                                onChange={(event) =>
                                    setActiveCategory(event.target.value)
                                }
                            >
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category === ALL_CATEGORIES
                                            ? 'Toutes les catégories'
                                            : category}
                                    </option>
                                ))}
                            </select>
                            <ChevronDown
                                size={16}
                                className="select-icon-chevron"
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="tri">Trier par</label>
                        <div className="select-icon-wrap">
                            <select
                                id="tri"
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
                    </div>
                    <div className="service-results-count">
                        <span>
                            {sortedServices.length} services affichés sur{' '}
                            {services.length}
                        </span>
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
            </section>

            <section className="cat-nav-sticky container">
                <nav className="cat-nav">
                    {categories.map((category) => {
                        const count =
                            category === ALL_CATEGORIES
                                ? services.length
                                : services.filter(
                                      (service) =>
                                          service.categorie === category,
                                  ).length;

                        return (
                            <button
                                key={category}
                                type="button"
                                className={`pill${activeCategory === category ? ' active' : ''}`}
                                onClick={() => setActiveCategory(category)}
                            >
                                {category}
                                <span className="cat-count">{count}</span>
                            </button>
                        );
                    })}
                </nav>
            </section>

            {sortedServices.length === 0 ? (
                <section className="section-gap container">
                    <p className="service-empty-state">
                        Aucun service ne correspond à votre recherche.
                    </p>
                </section>
            ) : (
                groupedServices.map((group) => (
                    <section
                        key={group.category}
                        className="section-gap reveal container"
                    >
                        <h2 style={{ fontSize: 21, color: 'var(--dag-navy)' }}>
                            {group.category}
                        </h2>
                        <div
                            className="grid-3 reveal-stagger"
                            style={{ marginTop: 22 }}
                        >
                            {group.services.map((service) => (
                                <div
                                    key={service.id}
                                    className="card photo-card"
                                >
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
                                                    background:
                                                        'var(--dag-navy)',
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
                                            className="btn-primary btn-service-request btn-block"
                                            style={{ marginTop: 16 }}
                                        >
                                            Demander ce service
                                            <ArrowRight
                                                size={15}
                                                className="btn-service-request-icon"
                                            />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                ))
            )}
        </PublicLayout>
    );
}
