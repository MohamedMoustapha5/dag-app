import { Head, router } from '@inertiajs/react';
import { Check, Lock, ShieldCheck, X } from 'lucide-react';
import { useState } from 'react';
import simulation from '@/routes/paiements/simulation';

type Props = {
    paiement: {
        id: string;
        montant: string;
        moyen: string | null;
        reference: string | null;
        facture: string;
    };
    retour: string;
};

const NAVY = '#071f41';
const BLUE = '#0166fe';
const MUTED = '#5b6b82';

// This page sits outside the .dag scope, so its buttons carry their own style.
const BOUTON = {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    padding: '13px 16px',
    borderRadius: 12,
    fontSize: 14,
    fontWeight: 700,
    fontFamily: 'inherit',
    cursor: 'pointer',
} as const;

function Ligne({ libelle, valeur }: { libelle: string; valeur: string }) {
    return (
        <div
            style={{
                display: 'flex',
                justifyContent: 'space-between',
                gap: 16,
                padding: '11px 0',
                borderBottom: '1px solid #e8edf6',
                fontSize: 14,
            }}
        >
            <span style={{ color: MUTED }}>{libelle}</span>
            <strong style={{ color: NAVY, textAlign: 'right' }}>
                {valeur}
            </strong>
        </div>
    );
}

export default function PaiementSimulation({ paiement, retour }: Props) {
    const [envoi, setEnvoi] = useState(false);
    const montant = Number(paiement.montant).toLocaleString('fr-FR');

    const decider = (resultat: 'succes' | 'echec') => {
        setEnvoi(true);
        router.post(
            simulation.decision(paiement.id).url,
            { resultat },
            { onFinish: () => setEnvoi(false) },
        );
    };

    return (
        <>
            <Head title="Confirmation du paiement" />

            <main
                style={{
                    minHeight: '100vh',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 18,
                    padding: '32px 20px',
                    background:
                        'linear-gradient(160deg, #eaf1ff 0%, #f6f9ff 55%, #ffffff 100%)',
                    fontFamily: "'Plus Jakarta Sans', sans-serif",
                }}
            >
                <img
                    src="/images/brand/dag-logo-pdf.png"
                    alt="DAG — Digital Agency Group"
                    style={{ height: 54 }}
                />

                <div
                    style={{
                        width: '100%',
                        maxWidth: 460,
                        background: '#fff',
                        borderRadius: 20,
                        boxShadow: '0 18px 50px rgba(7,31,65,0.14)',
                        overflow: 'hidden',
                    }}
                >
                    <div
                        style={{
                            background: NAVY,
                            color: '#fff',
                            padding: '22px 28px',
                        }}
                    >
                        <p
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 6,
                                fontSize: 11.5,
                                fontWeight: 700,
                                letterSpacing: 1.2,
                                textTransform: 'uppercase',
                                opacity: 0.85,
                                marginBottom: 6,
                            }}
                        >
                            <Lock size={12} /> Paiement sécurisé
                        </p>
                        <p style={{ fontSize: 13, opacity: 0.8 }}>
                            Montant à régler
                        </p>
                        <p
                            style={{
                                fontSize: 34,
                                fontWeight: 800,
                                lineHeight: 1.15,
                            }}
                        >
                            {montant}{' '}
                            <span style={{ fontSize: 16, fontWeight: 700 }}>
                                FCFA
                            </span>
                        </p>
                    </div>

                    <div style={{ padding: '10px 28px 26px' }}>
                        <Ligne libelle="Marchand" valeur="DAG — Digital Agency Group" />
                        <Ligne libelle="Facture" valeur={paiement.facture} />
                        <Ligne
                            libelle="Moyen de paiement"
                            valeur={paiement.moyen ?? '—'}
                        />
                        <Ligne
                            libelle="Référence de transaction"
                            valeur={paiement.reference ?? '—'}
                        />

                        <p
                            style={{
                                margin: '20px 0 18px',
                                padding: '12px 14px',
                                borderRadius: 12,
                                background: '#fdf3b3',
                                color: '#8a5a06',
                                fontSize: 12.5,
                                lineHeight: 1.55,
                            }}
                        >
                            <strong>Mode simulation.</strong> Cet écran remplace
                            la page du prestataire tant qu'aucun compte réel
                            n'est branché. Aucun argent n'est débité.
                        </p>

                        <div style={{ display: 'flex', gap: 12 }}>
                            <button
                                type="button"
                                style={{
                                    ...BOUTON,
                                    flex: 1,
                                    background: '#fff',
                                    color: BLUE,
                                    border: `1.5px solid ${BLUE}`,
                                }}
                                disabled={envoi}
                                onClick={() => decider('echec')}
                            >
                                <X size={16} /> Refuser
                            </button>
                            <button
                                type="button"
                                style={{
                                    ...BOUTON,
                                    flex: 1.4,
                                    background: BLUE,
                                    color: '#fff',
                                    border: `1.5px solid ${BLUE}`,
                                    boxShadow: '0 8px 20px rgba(1,102,254,0.28)',
                                }}
                                disabled={envoi}
                                onClick={() => decider('succes')}
                            >
                                <Check size={16} />
                                {envoi ? 'Traitement…' : 'Confirmer le paiement'}
                            </button>
                        </div>

                        <a
                            href={retour}
                            style={{
                                display: 'block',
                                textAlign: 'center',
                                marginTop: 18,
                                fontSize: 13,
                                color: MUTED,
                            }}
                        >
                            Annuler et retourner à mes factures
                        </a>
                    </div>
                </div>

                <p
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 6,
                        fontSize: 12,
                        color: MUTED,
                    }}
                >
                    <ShieldCheck size={14} /> Transaction chiffrée. DAG ne
                    conserve aucune donnée bancaire.
                </p>
            </main>
        </>
    );
}
