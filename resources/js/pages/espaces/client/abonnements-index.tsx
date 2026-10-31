import { Head, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import espaceClient from '@/routes/espace-client';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type AbonnementActif = {
    formuleNom: string;
    servicesInclus: string[];
    montant: string;
    dateSouscription: string | null;
    cycleDebut: string | null;
    echeance: string | null;
    progression: number;
};

type FactureHistorique = {
    id: string;
    echeance: string | null;
    moyenPaiement: string | null;
    statut: string;
};

type Props = {
    abonnement: AbonnementActif | null;
    historiqueFactures: FactureHistorique[];
};

function formatDateCourte(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    const date = new Date(`${dateIso}T00:00:00`);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
    }).format(date);
}

function formatMoisAnnee(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    const date = new Date(`${dateIso}T00:00:00`);
    const label = new Intl.DateTimeFormat('fr-FR', {
        month: 'long',
        year: 'numeric',
    }).format(date);
    return label.charAt(0).toUpperCase() + label.slice(1);
}

export default function AbonnementsIndex({
    abonnement,
    historiqueFactures,
}: Props) {
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
                <div className="espace-hero-content">
                    <h1>Abonnements</h1>
                    <p>Formule active, montant négocié avec l'équipe DAG.</p>
                </div>
            </div>

            {!abonnement ? (
                <div
                    className="card"
                    style={{ padding: 40, textAlign: 'center' }}
                >
                    <p
                        className="muted"
                        style={{ fontSize: 13.5, marginBottom: 16 }}
                    >
                        Vous n'avez pas d'abonnement actif.
                    </p>
                    <Link
                        href={espaceClient.services()}
                        className="btn-primary"
                    >
                        Découvrir nos formules
                    </Link>
                </div>
            ) : (
                <div className="grid-3">
                    <div
                        className="card"
                        style={{ padding: 28, gridColumn: 'span 2' }}
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
                                    }}
                                >
                                    <h2
                                        style={{
                                            fontSize: 17,
                                            color: 'var(--dag-navy)',
                                        }}
                                    >
                                        Formule {abonnement.formuleNom}
                                    </h2>
                                    <span className="chip chip-green">
                                        Actif
                                    </span>
                                </div>
                                <p
                                    className="muted"
                                    style={{
                                        fontSize: 13,
                                        marginTop: 6,
                                        maxWidth: 360,
                                    }}
                                >
                                    {abonnement.servicesInclus.join(' + ')}.
                                </p>
                            </div>
                            <div style={{ textAlign: 'right' }}>
                                <strong
                                    style={{
                                        fontSize: 26,
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
                                <p
                                    className="muted"
                                    style={{ fontSize: 12, marginTop: 2 }}
                                >
                                    Négocié le{' '}
                                    {formatDateCourte(
                                        abonnement.dateSouscription,
                                    )}
                                </p>
                            </div>
                        </div>

                        <div style={{ marginTop: 24 }}>
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    fontSize: 12.5,
                                }}
                                className="muted"
                            >
                                <span>
                                    Cycle en cours — débuté le{' '}
                                    {formatDateCourte(abonnement.cycleDebut)}
                                </span>
                                <span>
                                    Renouvellement le{' '}
                                    {formatDateCourte(abonnement.echeance)}
                                </span>
                            </div>
                            <div className="espace-formule-progress">
                                <div
                                    className="espace-formule-progress-bar"
                                    style={{
                                        width: `${abonnement.progression}%`,
                                    }}
                                />
                            </div>
                        </div>

                        <p
                            style={{
                                fontSize: 13,
                                fontWeight: 600,
                                marginTop: 20,
                                marginBottom: 8,
                            }}
                        >
                            Ce qui est inclus
                        </p>
                        <div
                            style={{
                                display: 'grid',
                                gridTemplateColumns:
                                    'repeat(auto-fit, minmax(160px, 1fr))',
                                gap: 4,
                            }}
                        >
                            {abonnement.servicesInclus.map((service) => (
                                <span
                                    key={service}
                                    className="espace-formule-check"
                                >
                                    <Check
                                        size={14}
                                        style={{ color: 'var(--dag-green)' }}
                                    />
                                    {service}
                                </span>
                            ))}
                        </div>

                        <div
                            style={{
                                display: 'flex',
                                gap: 10,
                                marginTop: 24,
                                flexWrap: 'wrap',
                            }}
                        >
                            <button
                                type="button"
                                className="btn-primary"
                                disabled
                                title="Disponible prochainement"
                            >
                                Renégocier ma formule
                            </button>
                            <button
                                type="button"
                                className="btn-outline"
                                disabled
                                title="Disponible prochainement"
                            >
                                Changer de formule
                            </button>
                        </div>
                    </div>

                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 14,
                                fontWeight: 600,
                                marginBottom: 16,
                            }}
                        >
                            Historique de facturation
                        </p>
                        {historiqueFactures.length === 0 ? (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune facture pour le moment.
                            </p>
                        ) : (
                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 14,
                                }}
                            >
                                {historiqueFactures.map((facture) => (
                                    <div
                                        key={facture.id}
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                        }}
                                    >
                                        <div>
                                            <p
                                                style={{
                                                    fontSize: 13,
                                                    fontWeight: 600,
                                                }}
                                            >
                                                {formatMoisAnnee(
                                                    facture.echeance,
                                                )}
                                            </p>
                                            <span
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {facture.moyenPaiement ?? '—'}
                                            </span>
                                        </div>
                                        <span
                                            className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[facture.statut] ??
                                                facture.statut}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                        <Link
                            href={espaceClient.factures()}
                            className="btn-outline btn-block"
                            style={{ marginTop: 20 }}
                        >
                            Voir toutes mes factures
                        </Link>
                    </div>
                </div>
            )}
        </>
    );
}
