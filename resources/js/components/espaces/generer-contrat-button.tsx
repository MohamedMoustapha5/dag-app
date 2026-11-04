import { router } from '@inertiajs/react';
import { useState } from 'react';
import contratsAdmin from '@/routes/espace-admin/contrats';

type Props = {
    demandeId: string;
    /** "lien" for table rows, "bouton" for a page header. */
    variante?: 'lien' | 'bouton';
};

/**
 * Action offered wherever a demande has been paid: drafts its contract from
 * the active model and the clause library. The contract is mandatory for
 * every paid service (it is also drafted automatically on payment).
 */
export default function GenererContratButton({
    demandeId,
    variante = 'lien',
}: Props) {
    const [enCours, setEnCours] = useState(false);

    const generer = () => {
        setEnCours(true);
        router.post(
            contratsAdmin.store().url,
            { demande_id: demandeId },
            { onFinish: () => setEnCours(false) },
        );
    };

    return (
        <button
            type="button"
            className={variante === 'bouton' ? 'btn-outline' : 'link-action'}
            style={
                variante === 'bouton'
                    ? { padding: '8px 16px', fontSize: 13 }
                    : undefined
            }
            disabled={enCours}
            title="Le contrat est obligatoire : génère celui de cette demande"
            onClick={generer}
        >
            {enCours ? 'Création…' : 'Générer le contrat'}
        </button>
    );
}
