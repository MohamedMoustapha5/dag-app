import { Head, Link } from '@inertiajs/react';
import {
    Briefcase,
    FileSignature,
    FileText,
    Receipt,
    TrendingDown,
    TrendingUp,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import PaiementsChart from '@/components/espaces/paiements-chart';
import RepartitionDonut from '@/components/espaces/repartition-donut';
import ServicesRanking from '@/components/espaces/services-ranking';
import {
    phrasesSurveillance,
    type Surveillance,
} from '@/lib/abonnement-surveillance';
import abonnementsAdmin from '@/routes/espace-admin/abonnements';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';
import contratsAdmin from '@/routes/espace-admin/contrats';
import demandesAdmin from '@/routes/espace-admin/demandes';
import entreprisesAdmin from '@/routes/espace-admin/entreprises';
import facturesAdmin from '@/routes/espace-admin/factures';

type Point = { label: string; montant: number };

type Props = {
    droits: Record<string, string>;
    kpis: {
        chiffreAffaires: { montant: number; evolution: number } | null;
        demandes: { enAttente: number; aTransmettreNovaTech: number } | null;
        contrats: { aSigner: number; relances: number } | null;
        placement: { taux: number; apprenantsPlaces: number } | null;
    };
    abonnementsASurveiller: Surveillance | null;
    paiements: {
        '7j': Point[];
        '30j': Point[];
        '3mois': Point[];
    } | null;
    repartitionCa: {
        total: number;
        segments: { label: string; montant: number }[];
    } | null;
    servicesPlusDemandes:
        | { label: string; total: number; novaTech: boolean }[]
        | null;
};

const PERIODES = [
    {
        key: '7j' as const,
        label: '7 jours',
        soustitre: 'Sur les 7 derniers jours',
    },
    {
        key: '30j' as const,
        label: '30 jours',
        soustitre: 'Sur les 30 derniers jours',
    },
    {
        key: '3mois' as const,
        label: '3 mois',
        soustitre: 'Sur les 3 derniers mois',
    },
];

const DONUT_COLORS = [
    'var(--dag-blue)',
    'var(--dag-orange)',
    'var(--dag-green)',
];

export default function AdminDashboard({
    droits,
    kpis,
    abonnementsASurveiller,
    paiements,
    repartitionCa,
    servicesPlusDemandes,
}: Props) {
    const [periode, setPeriode] = useState<'7j' | '30j' | '3mois'>('7j');
    const periodeActive =
        PERIODES.find((item) => item.key === periode) ?? PERIODES[0];
    const totalPeriode = (paiements?.[periode] ?? []).reduce(
        (somme, point) => somme + point.montant,
        0,
    );
    const aucunWidget =
        !kpis.chiffreAffaires &&
        !kpis.demandes &&
        !kpis.contrats &&
        !kpis.placement &&
        !paiements &&
        !repartitionCa &&
        !servicesPlusDemandes;

    const formatMontant = (montant: number) =>
        new Intl.NumberFormat('fr-FR').format(montant);

    const peutVoir = (domaine: string) => droits[domaine] !== 'AUCUN';

    const Evolution = ({ valeur }: { valeur: number }) => (
        <span
            style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 4,
                fontSize: 12.5,
                fontWeight: 700,
                color: valeur >= 0 ? 'var(--dag-green)' : 'var(--dag-red)',
            }}
        >
            {valeur >= 0 ? (
                <TrendingUp size={13} />
            ) : (
                <TrendingDown size={13} />
            )}
            {valeur >= 0 ? '+' : ''}
            {valeur}% vs mois dernier
        </span>
    );

    return (
        <>
            <Head title="Tableau de bord — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/office-glass-dubai.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Tableau de bord</h1>
                    <p>Vue d'ensemble de l'activité DAG, en temps réel.</p>
                </div>
            </div>

            {abonnementsASurveiller &&
                phrasesSurveillance(abonnementsASurveiller).length > 0 && (
                    <Link
                        href={abonnementsAdmin.index()}
                        role="alert"
                        className="card"
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 12,
                            flexWrap: 'wrap',
                            padding: '14px 20px',
                            marginBottom: 24,
                            borderLeft: '4px solid var(--dag-orange)',
                            textDecoration: 'none',
                        }}
                    >
                        <span style={{ fontSize: 14 }}>
                            <strong>Abonnements à traiter : </strong>
                            {phrasesSurveillance(abonnementsASurveiller).join(
                                ', ',
                            )}
                            .
                        </span>
                        <span
                            style={{
                                fontSize: 13,
                                fontWeight: 600,
                                color: 'var(--dag-blue)',
                            }}
                        >
                            Voir les abonnements
                        </span>
                    </Link>
                )}

            {(kpis.chiffreAffaires ||
                kpis.demandes ||
                kpis.contrats ||
                kpis.placement) && (
                <div
                    className="grid-4 espace-stats-grid"
                    style={{ marginBottom: 24 }}
                >
                    {kpis.chiffreAffaires && (
                        <div
                            className="card espace-stat-card"
                            style={{
                                padding: 22,
                                borderTopColor: 'var(--dag-blue)',
                            }}
                        >
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                Chiffre d'affaires (mois)
                            </span>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    margin: '6px 0',
                                }}
                            >
                                {formatMontant(kpis.chiffreAffaires.montant)}{' '}
                                FCFA
                            </strong>
                            <Evolution
                                valeur={kpis.chiffreAffaires.evolution}
                            />
                        </div>
                    )}

                    {kpis.demandes && (
                        <div
                            className="card espace-stat-card"
                            style={{
                                padding: 22,
                                borderTopColor: 'var(--dag-orange)',
                            }}
                        >
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                Demandes en attente
                            </span>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    margin: '6px 0',
                                }}
                            >
                                {kpis.demandes.enAttente}
                            </strong>
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                {kpis.demandes.aTransmettreNovaTech} à
                                transmettre à Nova-Tech
                            </span>
                        </div>
                    )}

                    {kpis.contrats && (
                        <div
                            className="card espace-stat-card"
                            style={{
                                padding: 22,
                                borderTopColor: 'var(--dag-red)',
                            }}
                        >
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                Contrats à signer
                            </span>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    margin: '6px 0',
                                }}
                            >
                                {kpis.contrats.aSigner}
                            </strong>
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                {kpis.contrats.relances} relances envoyées
                            </span>
                        </div>
                    )}

                    {kpis.placement && (
                        <div
                            className="card espace-stat-card"
                            style={{
                                padding: 22,
                                borderTopColor: 'var(--dag-green)',
                            }}
                        >
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                Taux de placement formation
                            </span>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    margin: '6px 0',
                                }}
                            >
                                {kpis.placement.taux}%
                            </strong>
                            <span className="muted" style={{ fontSize: 12.5 }}>
                                +{kpis.placement.apprenantsPlaces} apprenants
                                placés
                            </span>
                        </div>
                    )}
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <p style={{ fontSize: 14, fontWeight: 700, marginBottom: 16 }}>
                    Actions rapides
                </p>
                <div className="espace-quick-actions">
                    {peutVoir('DEMANDES_CLIENTS') && (
                        <Link
                            href={demandesAdmin.index()}
                            className="espace-action-btn"
                        >
                            <FileText size={16} />
                            Demandes clients
                            {(kpis.demandes?.enAttente ?? 0) > 0 && (
                                <span className="espace-action-badge">
                                    {kpis.demandes?.enAttente}
                                </span>
                            )}
                        </Link>
                    )}
                    {peutVoir('FACTURES_PAIEMENTS') && (
                        <Link
                            href={facturesAdmin.index()}
                            className="espace-action-btn"
                        >
                            <Receipt size={16} />
                            Factures & paiements
                        </Link>
                    )}
                    {peutVoir('CONTRATS_CLAUSES') && (
                        <Link
                            href={contratsAdmin.index()}
                            className="espace-action-btn"
                        >
                            <FileSignature size={16} />
                            Contrats & clauses
                            {(kpis.contrats?.aSigner ?? 0) > 0 && (
                                <span className="espace-action-badge">
                                    {kpis.contrats?.aSigner}
                                </span>
                            )}
                        </Link>
                    )}
                    {peutVoir('ENTREPRISES_OFFRES') && (
                        <Link
                            href={entreprisesAdmin.index()}
                            className="espace-action-btn"
                        >
                            <Briefcase size={16} />
                            Entreprises & offres
                        </Link>
                    )}
                    {peutVoir('DOSSIERS_APPRENANTS') && (
                        <Link
                            href={apprenantsAdmin.index()}
                            className="espace-action-btn"
                        >
                            <UserRound size={16} />
                            Dossiers apprenants
                        </Link>
                    )}
                    {peutVoir('ABONNEMENTS') && (
                        <Link
                            href={abonnementsAdmin.index()}
                            className="espace-action-btn"
                        >
                            <Receipt size={16} />
                            Abonnements
                        </Link>
                    )}
                </div>
            </div>

            {(paiements || repartitionCa) && (
                <div
                    className={
                        paiements && repartitionCa
                            ? 'split-layout-wide'
                            : undefined
                    }
                    style={{ marginBottom: 24, gap: 24 }}
                >
                    {paiements && (
                        <div className="card" style={{ padding: 24 }}>
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'flex-start',
                                    marginBottom: 20,
                                    flexWrap: 'wrap',
                                    gap: 14,
                                }}
                            >
                                <div>
                                    <p
                                        className="muted"
                                        style={{ fontSize: 13 }}
                                    >
                                        Paiements encaissés
                                    </p>
                                    <strong
                                        style={{
                                            display: 'block',
                                            fontSize: 28,
                                            fontWeight: 800,
                                            color: 'var(--dag-navy)',
                                            margin: '4px 0 2px',
                                        }}
                                    >
                                        {formatMontant(totalPeriode)} FCFA
                                    </strong>
                                    <span
                                        className="muted"
                                        style={{ fontSize: 12.5 }}
                                    >
                                        {periodeActive.soustitre}
                                    </span>
                                </div>
                                <div
                                    style={{
                                        display: 'flex',
                                        gap: 2,
                                        padding: 4,
                                        background: '#f1f2f6',
                                        borderRadius: 999,
                                    }}
                                >
                                    {PERIODES.map((item) => (
                                        <button
                                            key={item.key}
                                            type="button"
                                            onClick={() => setPeriode(item.key)}
                                            style={{
                                                border: 'none',
                                                borderRadius: 999,
                                                padding: '7px 16px',
                                                fontSize: 12.5,
                                                fontWeight: 700,
                                                cursor: 'pointer',
                                                background:
                                                    periode === item.key
                                                        ? '#fff'
                                                        : 'transparent',
                                                color:
                                                    periode === item.key
                                                        ? 'var(--dag-blue)'
                                                        : 'var(--dag-text-muted)',
                                                boxShadow:
                                                    periode === item.key
                                                        ? '0 2px 6px rgba(7, 31, 65, 0.1)'
                                                        : 'none',
                                                transition: 'all 0.15s ease',
                                            }}
                                        >
                                            {item.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <PaiementsChart series={paiements[periode]} />
                        </div>
                    )}

                    {repartitionCa && (
                        <div className="card" style={{ padding: 24 }}>
                            <p
                                style={{
                                    fontSize: 14,
                                    fontWeight: 700,
                                    marginBottom: 16,
                                }}
                            >
                                Répartition du chiffre d'affaires
                            </p>
                            <RepartitionDonut
                                segments={repartitionCa.segments.map(
                                    (segment, index) => ({
                                        label: segment.label,
                                        value: segment.montant,
                                        color: DONUT_COLORS[
                                            index % DONUT_COLORS.length
                                        ],
                                    }),
                                )}
                                centerValue={`${formatMontant(repartitionCa.total)}`}
                                centerLabel="FCFA ce mois"
                                legendAsPercentage
                            />
                        </div>
                    )}
                </div>
            )}

            {servicesPlusDemandes && (
                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Services les plus demandés
                    </p>
                    <ServicesRanking services={servicesPlusDemandes} />
                </div>
            )}

            {aucunWidget && (
                <p className="muted" style={{ fontSize: 13 }}>
                    Aucune statistique n'est affichée pour votre rôle : seuls
                    les raccourcis ci-dessus vous sont ouverts.
                </p>
            )}
        </>
    );
}
