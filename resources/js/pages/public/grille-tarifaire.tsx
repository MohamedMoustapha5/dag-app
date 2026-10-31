import { Head } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';

type Ligne = {
    id: string;
    prestation: string;
    tarif: string;
};

type Categorie = {
    categorie: string;
    ordre: number;
    lignes: Ligne[];
};

type GrilleTarifaireProps = {
    categories: Categorie[];
};

export default function GrilleTarifaire({ categories }: GrilleTarifaireProps) {
    return (
        <PublicLayout>
            <Head title="Grille tarifaire — DAG" />

            <section className="service-catalogue-intro reveal container">
                <div
                    className="service-catalogue-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/service-app-mobile.jpg')",
                    }}
                >
                    <div className="service-catalogue-hero-content">
                        <span className="badge">Tarifs indicatifs</span>
                        <h1>Grille tarifaire DAG</h1>
                        <p>
                            Fourchettes de départ en FCFA, à confirmer selon le
                            cahier des charges. Un devis précise les livrables,
                            délais, retouches et modalités de paiement.
                        </p>
                    </div>
                </div>
            </section>

            <section className="section-gap container">
                {categories.map((categorie, index) => (
                    <div
                        key={categorie.categorie}
                        style={{
                            marginBottom:
                                index < categories.length - 1 ? 36 : 0,
                        }}
                    >
                        <h2
                            style={{
                                fontSize: 17,
                                color: 'var(--dag-navy)',
                                marginBottom: 14,
                            }}
                        >
                            {categorie.ordre}. {categorie.categorie}
                        </h2>
                        <div className="data-table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Prestation</th>
                                        <th>Tarif indicatif (FCFA)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {categorie.lignes.map((ligne) => (
                                        <tr key={ligne.id}>
                                            <td style={{ fontWeight: 600 }}>
                                                {ligne.prestation}
                                            </td>
                                            <td className="muted">
                                                {ligne.tarif}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}

                <div className="card" style={{ padding: 24, marginTop: 36 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            color: 'var(--dag-navy)',
                            marginBottom: 10,
                        }}
                    >
                        Conditions commerciales
                    </p>
                    <ul
                        className="muted"
                        style={{
                            fontSize: 13,
                            lineHeight: 1.8,
                            paddingLeft: 18,
                        }}
                    >
                        <li>
                            DAG fonctionne actuellement exclusivement en ligne
                            et ne dispose pas encore de local physique. Les
                            échanges et le suivi se font dans notre application.
                        </li>
                        <li>
                            Les prix sont indicatifs et peuvent varier selon le
                            volume, les délais, les options et la complexité.
                        </li>
                        <li>
                            Les budgets publicitaires, licences, abonnements
                            logiciels, nom de domaine, hébergement, déplacements
                            et sous-traitance sont exclus sauf mention au devis.
                        </li>
                        <li>
                            Les prestations nécessitant un tournage ou une
                            présence physique sont organisées au cas par cas et
                            peuvent entraîner des frais supplémentaires.
                        </li>
                    </ul>
                </div>
            </section>
        </PublicLayout>
    );
}
