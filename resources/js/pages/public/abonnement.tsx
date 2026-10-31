import { Head, Link } from '@inertiajs/react';
import { Check, Rocket, Sparkles, TrendingUp } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import PublicLayout from '@/layouts/public-layout';
import { contact } from '@/routes';

type Formule = {
    id: string;
    nom: string;
    contenu: string | null;
    servicesInclus: string[];
    prixAffiche: string | null;
    populaire: boolean;
};

type AbonnementProps = {
    formules: Formule[];
};

const FORMULE_ICONS: Record<string, LucideIcon> = {
    Essentiel: Rocket,
    Croissance: TrendingUp,
    Premium: Sparkles,
};
const DEFAULT_FORMULE_ICON = Sparkles;

export default function Abonnement({ formules }: AbonnementProps) {
    return (
        <PublicLayout>
            <Head title="Formules d'abonnement — DAG" />

            <section className="subscription-hero-wrap reveal container">
                <div className="subscription-hero">
                    <div className="subscription-hero-overlay" />
                    <div className="subscription-hero-content">
                        <span className="badge">Abonnement</span>
                        <h1>Un accompagnement continu, à prix fixe</h1>
                        <p>
                            Choisissez la formule qui correspond à votre rythme
                            de croissance, sans engagement caché.
                        </p>
                    </div>
                </div>
            </section>

            <section className="section-gap container">
                <div className="grid-3 reveal-stagger">
                    {formules.map((formule) => {
                        const Icon =
                            FORMULE_ICONS[formule.nom] ?? DEFAULT_FORMULE_ICON;

                        return (
                            <div
                                key={formule.id}
                                className="card"
                                style={{
                                    padding: 28,
                                    display: 'flex',
                                    flexDirection: 'column',
                                    borderColor: formule.populaire
                                        ? 'var(--dag-blue)'
                                        : undefined,
                                    boxShadow: formule.populaire
                                        ? 'var(--dag-shadow-blue)'
                                        : undefined,
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        gap: 12,
                                    }}
                                >
                                    <span className="catalogue-category-icon">
                                        <Icon size={20} />
                                    </span>
                                    {formule.populaire && (
                                        <span className="badge">
                                            Le plus choisi
                                        </span>
                                    )}
                                </div>
                                <h3
                                    style={{
                                        marginTop: 16,
                                        fontSize: 19,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    {formule.nom}
                                </h3>
                                {formule.prixAffiche && (
                                    <p
                                        style={{
                                            marginTop: 10,
                                            fontSize: 26,
                                            fontWeight: 800,
                                            color: 'var(--dag-navy)',
                                            fontFamily: "'Sora',sans-serif",
                                        }}
                                    >
                                        {formule.prixAffiche}
                                    </p>
                                )}
                                {formule.contenu && (
                                    <p
                                        className="muted"
                                        style={{
                                            marginTop: 8,
                                            fontSize: 13.5,
                                        }}
                                    >
                                        {formule.contenu}
                                    </p>
                                )}
                                {formule.servicesInclus.length > 0 && (
                                    <ul
                                        style={{
                                            marginTop: 18,
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: 10,
                                            flex: 1,
                                        }}
                                    >
                                        {formule.servicesInclus.map(
                                            (service) => (
                                                <li
                                                    key={service}
                                                    style={{
                                                        display: 'flex',
                                                        gap: 8,
                                                        fontSize: 13.5,
                                                        alignItems: 'start',
                                                    }}
                                                >
                                                    <Check
                                                        size={16}
                                                        style={{
                                                            color: 'var(--dag-green)',
                                                            flex: 'none',
                                                            marginTop: 1,
                                                        }}
                                                    />
                                                    {service}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                                <div
                                    style={{
                                        marginTop: 20,
                                        paddingTop: 20,
                                        borderTop:
                                            '1px solid var(--dag-border)',
                                    }}
                                >
                                    <Link
                                        href={contact()}
                                        className={
                                            formule.populaire
                                                ? 'btn-primary btn-block'
                                                : 'btn-outline btn-block'
                                        }
                                    >
                                        Choisir cette formule
                                    </Link>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {formules.length === 0 && (
                    <p className="service-empty-state">
                        Aucune formule publiée pour le moment.
                    </p>
                )}
            </section>
        </PublicLayout>
    );
}
