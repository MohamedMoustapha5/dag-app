import { Head, Link } from '@inertiajs/react';
import { Check, CheckCircle2 } from 'lucide-react';
import { iconeFormule } from '@/lib/icone-formule';
import espaceClient from '@/routes/espace-client';
import espaceClientDemandes from '@/routes/espace-client/demandes';

type Formule = {
    id: string;
    nom: string;
    contenu: string | null;
    servicesInclus: string[];
    prixAffiche: string | null;
    populaire: boolean;
    souscrite: boolean;
};

type Props = {
    formules: Formule[];
};

/**
 * The subscription formules inside the client espace, laid out like the
 * public "Abonnement" page: choosing one opens a formule request.
 */
export default function FormulesIndex({ formules }: Props) {
    return (
        <>
            <Head title="Formules d'abonnement" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
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
                        <h1>Formules d'abonnement</h1>
                        <p>
                            Un accompagnement continu, à prix fixe : choisissez
                            la formule qui correspond à votre rythme.
                        </p>
                    </div>
                    <Link
                        href={espaceClient.abonnements()}
                        className="btn-outline"
                        style={{ background: '#fff' }}
                    >
                        Mes abonnements
                    </Link>
                </div>
            </div>

            <div className="grid-3" style={{ marginTop: 24 }}>
                {formules.map((formule) => {
                    const Icon = iconeFormule(formule.nom);
                    const misEnAvant = formule.populaire && !formule.souscrite;

                    return (
                        <div
                            key={formule.id}
                            className="card"
                            style={{
                                padding: 28,
                                display: 'flex',
                                flexDirection: 'column',
                                borderColor: formule.souscrite
                                    ? 'var(--dag-green)'
                                    : misEnAvant
                                      ? 'var(--dag-blue)'
                                      : undefined,
                                boxShadow: misEnAvant
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
                                {formule.souscrite ? (
                                    <span className="chip chip-green">
                                        Votre formule
                                    </span>
                                ) : (
                                    formule.populaire && (
                                        <span className="badge">
                                            Le plus choisi
                                        </span>
                                    )
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
                                    style={{ marginTop: 8, fontSize: 13.5 }}
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
                                    {formule.servicesInclus.map((service) => (
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
                                    ))}
                                </ul>
                            )}
                            <div
                                style={{
                                    marginTop: 20,
                                    paddingTop: 20,
                                    borderTop: '1px solid var(--dag-border)',
                                }}
                            >
                                {formule.souscrite ? (
                                    <Link
                                        href={espaceClient.abonnements()}
                                        className="btn-outline btn-block"
                                        style={{ gap: 8 }}
                                    >
                                        <CheckCircle2 size={16} />
                                        Voir mon abonnement
                                    </Link>
                                ) : (
                                    <Link
                                        href={espaceClientDemandes.create({
                                            query: { formule: formule.id },
                                        })}
                                        className={
                                            misEnAvant
                                                ? 'btn-primary btn-block'
                                                : 'btn-outline btn-block'
                                        }
                                    >
                                        Choisir cette formule
                                    </Link>
                                )}
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
        </>
    );
}
