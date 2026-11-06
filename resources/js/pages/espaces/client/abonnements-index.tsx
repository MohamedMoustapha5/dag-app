import { Head, Link, router } from '@inertiajs/react';
import { Check, RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import CycleMois, { type MoisCycle } from '@/components/espaces/cycle-mois';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import demandesClient from '@/routes/espace-client/demandes';
import espaceClient from '@/routes/espace-client';
import abonnementsRoutes from '@/routes/espace-client/abonnements';

type Etat = 'actif' | 'bientot' | 'expire' | 'suspendu' | 'termine';

type FactureAbonnement = {
    id: string;
    echeance: string | null;
    statut: string;
};

type Abonnement = {
    id: string;
    formuleNom: string;
    servicesInclus: string[];
    montant: string;
    dateSouscription: string | null;
    cycleDebut: string | null;
    echeance: string | null;
    progression: number;
    joursRestants: number | null;
    etat: Etat;
    peutRenouveler: boolean;
    demandeRenouvellement: string | null;
    mois: MoisCycle[];
    paiementDuMois: string | null;
    factures: FactureAbonnement[];
};

type Props = {
    resume: { actifs: number; aRenouveler: number; mensuel: string };
    abonnements: Abonnement[];
};

type Onglet = 'tous' | 'actifs' | 'a-renouveler' | 'termines';

const ONGLETS: { value: Onglet; label: string }[] = [
    { value: 'tous', label: 'Tous' },
    { value: 'actifs', label: 'En cours' },
    { value: 'a-renouveler', label: 'À renouveler' },
    { value: 'termines', label: 'Terminés' },
];

const ETATS: Record<Etat, { label: string; chip: string; barre: string }> = {
    actif: { label: 'Actif', chip: 'chip-green', barre: 'var(--dag-blue)' },
    bientot: {
        label: 'Expire bientôt',
        chip: 'chip-orange',
        barre: 'var(--dag-orange)',
    },
    expire: {
        label: 'Expiré',
        chip: 'chip-red',
        barre: 'var(--dag-red)',
    },
    suspendu: {
        label: 'Suspendu',
        chip: 'chip-gray',
        barre: 'var(--dag-text-muted)',
    },
    termine: {
        label: 'Terminé',
        chip: 'chip-gray',
        barre: 'var(--dag-text-muted)',
    },
};

const PAIEMENT_MOIS: Record<string, { label: string; chip: string }> = {
    PAYEE: { label: 'Payé ce mois', chip: 'chip-green' },
    PARTIELLEMENT_PAYEE: { label: 'Paiement partiel', chip: 'chip-orange' },
    EN_ATTENTE: { label: 'À payer ce mois', chip: 'chip-orange' },
};

function formatDate(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${dateIso}T00:00:00`));
}

function formatMoisAnnee(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }

    const label = new Intl.DateTimeFormat('fr-FR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${dateIso}T00:00:00`));

    return label.charAt(0).toUpperCase() + label.slice(1);
}

function libelleRestant(abonnement: Abonnement): string {
    const jours = abonnement.joursRestants;

    if (abonnement.etat === 'termine') {
        return `Terminé le ${formatDate(abonnement.echeance)}`;
    }
    if (jours === null) {
        return 'Échéance non définie';
    }
    if (jours < 0) {
        return `Expiré depuis ${-jours} jour${-jours > 1 ? 's' : ''}`;
    }
    if (jours === 0) {
        return "Se termine aujourd'hui";
    }

    return `${jours} jour${jours > 1 ? 's' : ''} restant${jours > 1 ? 's' : ''}`;
}

function classer(abonnement: Abonnement): Onglet {
    if (abonnement.etat === 'termine') {
        return 'termines';
    }

    return abonnement.peutRenouveler ? 'a-renouveler' : 'actifs';
}

export default function AbonnementsIndex({ resume, abonnements }: Props) {
    const [onglet, setOnglet] = useState<Onglet>('tous');
    const [enCours, setEnCours] = useState<string | null>(null);

    const filtres = useMemo(
        () =>
            onglet === 'tous'
                ? abonnements
                : abonnements.filter(
                      (abonnement) => classer(abonnement) === onglet,
                  ),
        [abonnements, onglet],
    );

    const renouveler = (abonnement: Abonnement) => {
        router.post(
            abonnementsRoutes.renouveler(abonnement.id).url,
            {},
            {
                preserveScroll: true,
                onStart: () => setEnCours(abonnement.id),
                onFinish: () => setEnCours(null),
            },
        );
    };

    return (
        <>
            <Head title="Abonnements" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/phone-chart-app.jpg')",
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
                        <h1>Abonnements</h1>
                        <p>
                            Tous vos abonnements, leur progression et leur
                            renouvellement.
                        </p>
                    </div>
                    <Link
                        href={espaceClient.formules()}
                        className="btn-primary"
                    >
                        + Souscrire une formule
                    </Link>
                </div>
            </div>

            {abonnements.length === 0 ? (
                <div
                    className="card"
                    style={{ padding: 40, textAlign: 'center' }}
                >
                    <p
                        className="muted"
                        style={{ fontSize: 13.5, marginBottom: 16 }}
                    >
                        Vous n'avez pas encore d'abonnement.
                    </p>
                    <Link
                        href={espaceClient.formules()}
                        className="btn-primary"
                    >
                        Découvrir nos formules
                    </Link>
                </div>
            ) : (
                <>
                    <div
                        className="grid-3 espace-stats-grid"
                        style={{ marginBottom: 24 }}
                    >
                        <div
                            className="card espace-stat-card"
                            style={{ padding: 22 }}
                        >
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Abonnements en cours
                            </p>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {resume.actifs}
                            </strong>
                        </div>
                        <div
                            className="card espace-stat-card"
                            style={{ padding: 22 }}
                        >
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                À renouveler
                            </p>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    color:
                                        resume.aRenouveler > 0
                                            ? 'var(--dag-orange-ink)'
                                            : 'var(--dag-navy)',
                                }}
                            >
                                {resume.aRenouveler}
                            </strong>
                        </div>
                        <div
                            className="card espace-stat-card"
                            style={{ padding: 22 }}
                        >
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Total mensuel
                            </p>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 24,
                                    fontWeight: 800,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {resume.mensuel} FCFA
                            </strong>
                        </div>
                    </div>

                    <div className="account-tabs" style={{ marginBottom: 24 }}>
                        {ONGLETS.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                className={`account-tab${onglet === item.value ? ' active' : ''}`}
                                onClick={() => setOnglet(item.value)}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>

                    {filtres.length === 0 ? (
                        <p className="data-table-empty">
                            Aucun abonnement dans cette catégorie.
                        </p>
                    ) : (
                        <div
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 16,
                            }}
                        >
                            {filtres.map((abonnement) => {
                                const etat = ETATS[abonnement.etat];
                                const paiement =
                                    PAIEMENT_MOIS[
                                        abonnement.paiementDuMois ?? ''
                                    ] ?? null;

                                return (
                                    <div
                                        key={abonnement.id}
                                        className="card"
                                        style={{ padding: 24 }}
                                    >
                                        <div
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'flex-start',
                                                gap: 16,
                                                flexWrap: 'wrap',
                                            }}
                                        >
                                            <div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 10,
                                                        flexWrap: 'wrap',
                                                    }}
                                                >
                                                    <h2
                                                        style={{
                                                            fontSize: 17,
                                                            color: 'var(--dag-navy)',
                                                        }}
                                                    >
                                                        Formule{' '}
                                                        {abonnement.formuleNom}
                                                    </h2>
                                                    <span
                                                        className={`chip ${etat.chip}`}
                                                    >
                                                        {etat.label}
                                                    </span>
                                                    {paiement && (
                                                        <span
                                                            className={`chip ${paiement.chip}`}
                                                        >
                                                            {paiement.label}
                                                        </span>
                                                    )}
                                                </div>
                                                <p
                                                    className="muted"
                                                    style={{
                                                        fontSize: 12.5,
                                                        marginTop: 6,
                                                    }}
                                                >
                                                    Souscrit le{' '}
                                                    {formatDate(
                                                        abonnement.dateSouscription,
                                                    )}
                                                </p>
                                            </div>
                                            <div style={{ textAlign: 'right' }}>
                                                <strong
                                                    style={{
                                                        fontSize: 24,
                                                        fontWeight: 800,
                                                        color: 'var(--dag-navy)',
                                                    }}
                                                >
                                                    {abonnement.montant}
                                                </strong>
                                                <span
                                                    className="muted"
                                                    style={{ fontSize: 12.5 }}
                                                >
                                                    {' '}
                                                    FCFA/mois
                                                </span>
                                            </div>
                                        </div>

                                        <div style={{ marginTop: 20 }}>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    justifyContent:
                                                        'space-between',
                                                    gap: 12,
                                                    flexWrap: 'wrap',
                                                    fontSize: 12.5,
                                                }}
                                                className="muted"
                                            >
                                                <span>
                                                    Du{' '}
                                                    {formatDate(
                                                        abonnement.cycleDebut,
                                                    )}{' '}
                                                    au{' '}
                                                    {formatDate(
                                                        abonnement.echeance,
                                                    )}
                                                </span>
                                                <span
                                                    style={{
                                                        fontWeight: 600,
                                                        color:
                                                            abonnement.etat ===
                                                                'expire' ||
                                                            abonnement.etat ===
                                                                'bientot'
                                                                ? etat.barre
                                                                : undefined,
                                                    }}
                                                >
                                                    {libelleRestant(abonnement)}
                                                </span>
                                            </div>
                                            {abonnement.mois.length > 0 ? (
                                                <CycleMois
                                                    mois={abonnement.mois}
                                                    couleur={etat.barre}
                                                />
                                            ) : (
                                                <div className="espace-formule-progress">
                                                    <div
                                                        className="espace-formule-progress-bar"
                                                        style={{
                                                            width: `${abonnement.progression}%`,
                                                            background:
                                                                etat.barre,
                                                        }}
                                                    />
                                                </div>
                                            )}
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                Cycle écoulé à{' '}
                                                {abonnement.progression} %
                                            </p>
                                        </div>

                                        {abonnement.servicesInclus.length >
                                            0 && (
                                            <div
                                                style={{
                                                    display: 'grid',
                                                    gridTemplateColumns:
                                                        'repeat(auto-fit, minmax(180px, 1fr))',
                                                    gap: 4,
                                                    marginTop: 16,
                                                }}
                                            >
                                                {abonnement.servicesInclus.map(
                                                    (service) => (
                                                        <span
                                                            key={service}
                                                            className="espace-formule-check"
                                                        >
                                                            <Check
                                                                size={14}
                                                                style={{
                                                                    color: 'var(--dag-green)',
                                                                }}
                                                            />
                                                            {service}
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        )}

                                        {abonnement.factures.length > 0 && (
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 8,
                                                    flexWrap: 'wrap',
                                                    marginTop: 18,
                                                    alignItems: 'center',
                                                }}
                                            >
                                                <span
                                                    className="muted"
                                                    style={{ fontSize: 12.5 }}
                                                >
                                                    Dernières factures :
                                                </span>
                                                {abonnement.factures.map(
                                                    (facture) => (
                                                        <span
                                                            key={facture.id}
                                                            style={{
                                                                display:
                                                                    'inline-flex',
                                                                alignItems:
                                                                    'center',
                                                                gap: 6,
                                                                fontSize: 12.5,
                                                            }}
                                                        >
                                                            {formatMoisAnnee(
                                                                facture.echeance,
                                                            )}
                                                            <span
                                                                className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                                                            >
                                                                {STATUT_LABEL[
                                                                    facture
                                                                        .statut
                                                                ] ??
                                                                    facture.statut}
                                                            </span>
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        )}

                                        <div
                                            style={{
                                                display: 'flex',
                                                gap: 10,
                                                marginTop: 20,
                                                flexWrap: 'wrap',
                                            }}
                                        >
                                            {abonnement.demandeRenouvellement && (
                                                <Link
                                                    href={demandesClient.show(
                                                        abonnement.demandeRenouvellement,
                                                    )}
                                                    className="btn-primary"
                                                >
                                                    Renouvellement demandé :
                                                    suivre
                                                </Link>
                                            )}
                                            {abonnement.peutRenouveler &&
                                                !abonnement.demandeRenouvellement && (
                                                    <button
                                                        type="button"
                                                        className="btn-primary"
                                                        disabled={
                                                            enCours ===
                                                            abonnement.id
                                                        }
                                                        onClick={() =>
                                                            renouveler(
                                                                abonnement,
                                                            )
                                                        }
                                                    >
                                                        <RefreshCw size={14} />{' '}
                                                        {enCours ===
                                                        abonnement.id
                                                            ? 'Envoi...'
                                                            : 'Renouveler'}
                                                    </button>
                                                )}
                                            <Link
                                                href={espaceClient.factures()}
                                                className="btn-outline"
                                            >
                                                Mes factures
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </>
            )}
        </>
    );
}
