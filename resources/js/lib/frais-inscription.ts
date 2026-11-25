import { separerMilliers } from '@/lib/montant';

/**
 * Registration fee of a formation session as shown to everyone: "75 000 FCFA",
 * or a neutral wording while DAG has not published it yet.
 */
export function libelleFraisInscription(prix: string | number | null): string {
    if (prix === null || prix === '') {
        return 'Tarif bientôt disponible';
    }

    return `${separerMilliers(Number(prix))} FCFA`;
}
