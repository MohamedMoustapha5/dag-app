import { Head, router } from '@inertiajs/react';
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

export default function PaiementSimulation({ paiement, retour }: Props) {
    const [envoi, setEnvoi] = useState(false);

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
            <Head title="Paiement — simulation" />

            <main
                style={{
                    minHeight: '100vh',
                    display: 'grid',
                    placeItems: 'center',
                    padding: 24,
                    background: '#f4f7fd',
                }}
            >
                <div
                    style={{
                        width: '100%',
                        maxWidth: 440,
                        background: '#fff',
                        borderRadius: 18,
                        padding: 32,
                        boxShadow: '0 10px 40px rgba(7,31,65,0.12)',
                    }}
                >
                    <p
                        style={{
                            display: 'inline-block',
                            background: '#fdf3b3',
                            color: '#8a5a06',
                            fontWeight: 700,
                            fontSize: 12,
                            padding: '4px 12px',
                            borderRadius: 999,
                            marginBottom: 16,
                        }}
                    >
                        Environnement de simulation
                    </p>
                    <h1 style={{ fontSize: 22, color: '#071f41' }}>
                        Confirmer le paiement
                    </h1>
                    <p
                        style={{
                            color: '#5b6b82',
                            fontSize: 14,
                            lineHeight: 1.6,
                            margin: '8px 0 20px',
                        }}
                    >
                        Cet écran remplace la page du prestataire de paiement
                        tant qu'aucun compte réel n'est branché. Aucun argent
                        n'est débité.
                    </p>

                    <dl
                        style={{
                            display: 'grid',
                            gridTemplateColumns: 'auto 1fr',
                            gap: '8px 16px',
                            fontSize: 14,
                            marginBottom: 24,
                        }}
                    >
                        <dt style={{ color: '#5b6b82' }}>Facture</dt>
                        <dd style={{ fontWeight: 600 }}>{paiement.facture}</dd>
                        <dt style={{ color: '#5b6b82' }}>Moyen</dt>
                        <dd style={{ fontWeight: 600 }}>
                            {paiement.moyen ?? '—'}
                        </dd>
                        <dt style={{ color: '#5b6b82' }}>Référence</dt>
                        <dd>{paiement.reference ?? '—'}</dd>
                        <dt style={{ color: '#5b6b82' }}>Montant</dt>
                        <dd
                            style={{
                                fontWeight: 800,
                                fontSize: 18,
                                color: '#0166fe',
                            }}
                        >
                            {Number(paiement.montant).toLocaleString('fr-FR')}{' '}
                            FCFA
                        </dd>
                    </dl>

                    <div style={{ display: 'flex', gap: 12 }}>
                        <button
                            type="button"
                            className="btn-outline"
                            style={{ flex: 1 }}
                            disabled={envoi}
                            onClick={() => decider('echec')}
                        >
                            Refuser
                        </button>
                        <button
                            type="button"
                            className="btn-primary"
                            style={{ flex: 1 }}
                            disabled={envoi}
                            onClick={() => decider('succes')}
                        >
                            Confirmer
                        </button>
                    </div>

                    <a
                        href={retour}
                        style={{
                            display: 'block',
                            textAlign: 'center',
                            marginTop: 18,
                            fontSize: 13,
                            color: '#5b6b82',
                        }}
                    >
                        Retour à mes factures
                    </a>
                </div>
            </main>
        </>
    );
}
