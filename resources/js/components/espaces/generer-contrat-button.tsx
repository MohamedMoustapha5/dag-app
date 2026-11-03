import { router } from '@inertiajs/react';
import { useState } from 'react';
import contratsAdmin from '@/routes/espace-admin/contrats';

type Props = {
    demandeId: string;
    /** "lien" for table rows, "bouton" for a page header. */
    variante?: 'lien' | 'bouton';
};

/**
 * Optional action offered wherever a demande has been paid: drafts its
 * contract from the active model and the clause library. Nothing requires
 * it, so it is only ever a proposal.
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
            title="Facultatif : génère le contrat de cette demande"
            onClick={generer}
        >
            {enCours ? 'Création…' : 'Générer le contrat'}
        </button>
    );
}
