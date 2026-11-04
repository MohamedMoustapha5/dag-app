import { Check, Clock } from 'lucide-react';

export type MoisCycle = {
    label: string;
    annee: number;
    poids: number;
    avancement: number;
    courant: boolean;
    facture: string | null;
};

type Props = {
    mois: MoisCycle[];
    couleur?: string;
    compact?: boolean;
};

function libelleFacture(statut: string | null): string | null {
    if (statut === 'PAYEE') {
        return 'Payé';
    }
    if (statut === 'PARTIELLEMENT_PAYEE') {
        return 'Partiel';
    }
    if (statut === null) {
        return null;
    }

    return 'À payer';
}

/**
 * Progress bar of a billing cycle split into its months, each one showing how
 * far it has elapsed and whether its invoice is paid.
 */
export default function CycleMois({
    mois,
    couleur = 'var(--dag-blue)',
    compact = false,
}: Props) {
    if (mois.length === 0) {
        return null;
    }

    return (
        <div style={{ display: 'flex', gap: 4, margin: '10px 0 6px' }}>
            {mois.map((segment, index) => {
                const paiement = libelleFacture(segment.facture);

                return (
                    <div
                        key={`${segment.annee}-${segment.label}-${index}`}
                        style={{ flex: segment.poids, minWidth: 0 }}
                        title={`${segment.label} ${segment.annee}${paiement ? ` : ${paiement}` : ''}`}
                    >
                        <div
                            style={{
                                height: 6,
                                borderRadius: 999,
                                background: '#eef0f6',
                                overflow: 'hidden',
                            }}
                        >
                            <div
                                style={{
                                    width: `${segment.avancement}%`,
                                    height: '100%',
                                    background: couleur,
                                    borderRadius: 999,
                                }}
                            />
                        </div>
                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 4,
                                marginTop: 6,
                                fontSize: compact ? 11 : 12,
                                fontWeight: segment.courant ? 700 : 500,
                                color: segment.courant
                                    ? 'var(--dag-navy)'
                                    : 'var(--dag-text-muted)',
                                whiteSpace: 'nowrap',
                            }}
                        >
                            <span>{segment.label}</span>
                            {segment.facture === 'PAYEE' && (
                                <Check
                                    size={compact ? 11 : 13}
                                    style={{ color: 'var(--dag-green)' }}
                                    aria-label="Payé"
                                />
                            )}
                            {segment.facture !== null &&
                                segment.facture !== 'PAYEE' && (
                                    <Clock
                                        size={compact ? 11 : 13}
                                        style={{
                                            color: 'var(--dag-orange-ink)',
                                        }}
                                        aria-label="Paiement en attente"
                                    />
                                )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
