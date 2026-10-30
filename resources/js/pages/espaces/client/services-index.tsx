import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronDown, RotateCcw, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import EspaceClientLayout, {
    type EspaceClientInfo,
} from '@/layouts/espace-client-layout';
import demandes from '@/routes/espace-client/demandes';

type Service = {
    id: string;
    nom: string;
    description: string | null;
    categorie: string;
    image: string | null;
};

type Props = {
    client: EspaceClientInfo;
    services: Service[];
};

type SortOrder = 'catalogue' | 'nom-asc' | 'nom-desc';

const ALL_CATEGORIES = 'Toutes les catégories';

export default function ServicesIndex({ client, services }: Props) {
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
        <EspaceClientLayout client={client}>
            <Head title="Services" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/phone-social-burst.jpg')",
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

            <div
                className="service-filter-toolbar"
                style={{ marginBottom: 20 }}
            >
                <div className="service-filter-search">
                    <label htmlFor="search">Rechercher un service</label>
                    <div className="input-icon-wrap">
                        <Search size={16} className="input-icon-left" />
                        <input
                            id="search"
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Ex. community management..."
                            style={search ? { paddingRight: 36 } : undefined}
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
                                    {category}
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

            <div className="status-tabs">
                {categories.map((category) => {
                    const count =
                        category === ALL_CATEGORIES
                            ? services.length
                            : services.filter(
                                  (service) => service.categorie === category,
                              ).length;

                    return (
                        <button
                            key={category}
                            type="button"
                            className={`pill${activeCategory === category ? ' active' : ''}`}
                            onClick={() => setActiveCategory(category)}
                        >
                            {category === ALL_CATEGORIES
                                ? 'Tous les services'
                                : category}{' '}
                            {count}
                        </button>
                    );
                })}
            </div>

            {sortedServices.length === 0 ? (
                <p className="data-table-empty">
                    Aucun service ne correspond à votre recherche.
                </p>
            ) : (
                groupedServices.map((group) => (
                    <section key={group.category} style={{ marginBottom: 36 }}>
                        <h2
                            style={{
                                fontSize: 17,
                                color: 'var(--dag-navy)',
                                marginBottom: 18,
                            }}
                        >
                            {group.category}
                        </h2>
                        <div className="grid-3">
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
                                            href={demandes.create({
                                                query: { service: service.id },
                                            })}
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
        </EspaceClientLayout>
    );
}
