import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PublicLayout from '@/layouts/public-layout';

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

const ALL_CATEGORIES = 'Toutes';

export default function Catalogue({ services }: CatalogueProps) {
    const [search, setSearch] = useState('');
    const [activeCategory, setActiveCategory] =
        useState<string>(ALL_CATEGORIES);

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
                        <span className="badge">Catalogue</span>
                        <h1>Tous nos services, au même endroit</h1>
                        <p>
                            Développement, marketing, design, cybersécurité :
                            trouvez le service adapté à votre besoin.
                        </p>
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

            <section className="section-gap container">
                <div className="service-filter-toolbar">
                    <div className="service-filter-search">
                        <label htmlFor="search">Rechercher</label>
                        <input
                            id="search"
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Nom ou mot-clé du service"
                        />
                    </div>
                    <div className="service-results-count">
                        {filteredServices.length} service
                        {filteredServices.length > 1 ? 's' : ''}
                    </div>
                </div>

                {filteredServices.length === 0 ? (
                    <p className="service-empty-state">
                        Aucun service ne correspond à votre recherche.
                    </p>
                ) : (
                    <div
                        className="grid-3 reveal-stagger"
                        style={{ marginTop: 26 }}
                    >
                        {filteredServices.map((service) => (
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
                                        <span className="chip chip-blue photo-card-badge">
                                            {service.categorie}
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
                                    {service.fournisseur && (
                                        <p
                                            className="muted"
                                            style={{
                                                marginTop: 12,
                                                fontSize: 11.5,
                                            }}
                                        >
                                            Fourni par {service.fournisseur}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
