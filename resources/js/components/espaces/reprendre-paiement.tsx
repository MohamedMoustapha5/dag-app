import { router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { useState } from 'react';
import paiements from '@/routes/paiements';

/**
 * Shown while an online payment of the facture was started but not
 * finished (the payer left the provider's page, for instance). It asks the
 * provider where the attempt stands, then credits it, reopens the payment,
 * or sends the payer back to the same payment page: nothing is paid twice.
 */
export default function ReprendrePaiement({
    factureId,
    alignement = 'end',
}: {
    factureId: string;
    alignement?: 'start' | 'end';
}) {
    const [envoi, setEnvoi] = useState(false);

    const reprendre = () => {
        router.post(
            paiements.reprendre(factureId).url,
            {},
            {
                preserveScroll: true,
                onStart: () => setEnvoi(true),
                onFinish: () => setEnvoi(false),
            },
        );
    };

    return (
        <span
            style={{
                display: 'inline-flex',
                flexDirection: 'column',
                alignItems: alignement === 'end' ? 'flex-end' : 'flex-start',
                gap: 4,
            }}
        >
            <button
                type="button"
                className="btn-primary"
                disabled={envoi}
                onClick={reprendre}
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: 6,
                    padding: '8px 16px',
                    fontSize: 13,
                }}
            >
                <RotateCcw size={14} aria-hidden="true" />
                {envoi ? 'Vérification…' : 'Reprendre le paiement'}
            </button>
            <span className="muted" style={{ fontSize: 11.5 }}>
                Paiement commencé, pas encore finalisé
            </span>
        </span>
    );
}
