import { Head, Link, router, useForm } from '@inertiajs/react';
import { BadgeCheck, CalendarClock, Check, Lock, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import PayerFactureModal, {
    type MoyenPaiement,
} from '@/components/espaces/payer-facture-modal';
import ReprendrePaiement from '@/components/espaces/reprendre-paiement';
import { formatMontant } from '@/lib/montant';
import offres from '@/routes/espace-client/offres';
import recrutement from '@/routes/espace-client/recrutement';

type Forfait = {
    id: string;
    libelle: string;
    dureeMois: number;
    prix: string;
};

type Props = {
    forfaits: Forfait[];
    acces: { actif: boolean; fin: string | null };
    factureEnAttente: {
        id: string;
        reference: string;
        objet: string;
        forfait: string;
        solde: string;
        paiementEnAttente: boolean;
    } | null;
    historique: {
        id: string;
        libelle: string;
        origine: 'PAIEMENT' | 'OFFERT';
        montant: string;
        debut: string | null;
        fin: string | null;
    }[];
    moyensPaiement: MoyenPaiement[];
};

const AVANTAGES = [
    'Publiez vos offres de stage et d’emploi',
    'Recevez les candidats formés par DAG',
    'Suivez chaque candidature jusqu’au placement',
];

function dateLongue(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function RecrutementAbonnement({
    forfaits,
    acces,
    factureEnAttente,
    historique,
    moyensPaiement,
}: Props) {
    const [paiementOuvert, setPaiementOuvert] = useState(false);
    const form = useForm({ forfait_id: '' });

    // Right after choosing a forfait, its payment window opens.
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;

            if (flash?.payerFacture) {
                setPaiementOuvert(true);
            }
        });
    }, []);

    const choisir = (forfait: Forfait) => {
        form.transform(() => ({ forfait_id: forfait.id }));
        form.post(recrutement.souscrire().url, { preserveScroll: true });
    };

    // The best value: the lowest price per month.
    const prixMensuel = (forfait: Forfait) =>
        Number(forfait.prix) / forfait.dureeMois;
    const meilleur = forfaits.reduce<Forfait | null>(
        (garde, forfait) =>
            garde === null || prixMensuel(forfait) < prixMensuel(garde)
                ? forfait
                : garde,
        null,
    );

    return (
        <>
            <Head title="Abonnement recrutement" />

            <div style={{ marginBottom: 24 }}>
                <h1 style={{ fontSize: 24, color: 'var(--dag-navy)' }}>
                    Abonnement recrutement
                </h1>
                <p className="muted" style={{ marginTop: 6, fontSize: 14 }}>
                    Accédez à vos pages recrutement : offres d’emploi et
                    candidats formés par DAG.
                </p>
            </div>

            <div
                className={`card abonnement-etat${acces.actif ? ' actif' : ''}`}
                style={{ padding: 20, marginBottom: 24 }}
            >
                {acces.actif && acces.fin ? (
                    <>
                        <p className="abonnement-etat-titre">
                            <BadgeCheck size={18} aria-hidden="true" />
                            Accès recrutement actif jusqu’au{' '}
                            {dateLongue(acces.fin)}
                        </p>
                        <p className="muted" style={{ fontSize: 13 }}>
                            Vous pouvez renouveler dès maintenant : la nouvelle
                            période s’ajoute à la fin de celle en cours, sans
                            perdre aucun jour.
                        </p>
                        <Link
                            href={offres.index()}
                            className="link-action"
                            style={{ marginTop: 8, display: 'inline-block' }}
                        >
                            Gérer mes offres →
                        </Link>
                    </>
                ) : (
                    <>
                        <p className="abonnement-etat-titre">
                            <Lock size={18} aria-hidden="true" />
                            Vos pages recrutement sont fermées
                        </p>
                        <p className="muted" style={{ fontSize: 13 }}>
                            Choisissez une durée ci-dessous et réglez-la en
                            ligne : l’accès s’ouvre dès le paiement confirmé.
                        </p>
                    </>
                )}
            </div>

            {factureEnAttente && (
                <div
                    className="card"
                    style={{
                        padding: 20,
                        marginBottom: 24,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 16,
                        flexWrap: 'wrap',
                        borderLeft: '4px solid var(--dag-orange)',
                    }}
                >
                    <div>
                        <p style={{ fontWeight: 700 }}>
                            Abonnement {factureEnAttente.forfait} à régler
                        </p>
                        <p className="muted" style={{ fontSize: 13 }}>
                            Facture {factureEnAttente.reference} ·{' '}
                            {formatMontant(factureEnAttente.solde)}
                        </p>
                    </div>
                    {factureEnAttente.paiementEnAttente ? (
                        <ReprendrePaiement factureId={factureEnAttente.id} />
                    ) : (
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={() => setPaiementOuvert(true)}
                        >
                            Payer {formatMontant(factureEnAttente.solde)}
                        </button>
                    )}
                </div>
            )}

            <div className="abonnement-forfaits">
                {forfaits.map((forfait) => {
                    const estMeilleur =
                        meilleur?.id === forfait.id && forfaits.length > 1;
                    const choisi =
                        factureEnAttente?.forfait === forfait.libelle;

                    return (
                        <div
                            key={forfait.id}
                            className={`card abonnement-forfait${estMeilleur ? ' meilleur' : ''}`}
                        >
                            {estMeilleur && (
                                <span className="abonnement-forfait-badge">
                                    <Sparkles size={13} aria-hidden="true" />
                                    Le plus avantageux
                                </span>
                            )}
                            <p className="abonnement-forfait-duree">
                                {forfait.libelle}
                            </p>
                            <p className="abonnement-forfait-prix">
                                {formatMontant(forfait.prix)}
                            </p>
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                {forfait.dureeMois > 1
                                    ? `soit ${formatMontant(String(Math.round(prixMensuel(forfait))))} par mois`
                                    : 'sans engagement'}
                            </p>
                            <ul className="abonnement-forfait-avantages">
                                {AVANTAGES.map((avantage) => (
                                    <li key={avantage}>
                                        <Check size={14} aria-hidden="true" />
                                        {avantage}
                                    </li>
                                ))}
                            </ul>
                            <button
                                type="button"
                                className={
                                    estMeilleur ? 'btn-primary' : 'btn-outline'
                                }
                                disabled={form.processing}
                                onClick={() => choisir(forfait)}
                                style={{ width: '100%' }}
                            >
                                {choisi
                                    ? 'Formule choisie'
                                    : acces.actif
                                      ? `Renouveler ${forfait.libelle}`
                                      : `Choisir ${forfait.libelle}`}
                            </button>
                        </div>
                    );
                })}
            </div>
            {form.errors.forfait_id && (
                <p style={{ color: 'var(--dag-red)', marginTop: 8 }}>
                    {form.errors.forfait_id}
                </p>
            )}

            {historique.length > 0 && (
                <div className="card" style={{ padding: 24, marginTop: 28 }}>
                    <h2
                        style={{
                            fontSize: 15,
                            marginBottom: 12,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                        }}
                    >
                        <CalendarClock size={16} aria-hidden="true" />
                        Historique de l’accès recrutement
                    </h2>
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Durée</th>
                                    <th>Période</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                {historique.map((ligne) => (
                                    <tr key={ligne.id}>
                                        <td
                                            data-label="Durée"
                                            style={{ fontWeight: 600 }}
                                        >
                                            {ligne.libelle}
                                        </td>
                                        <td data-label="Période">
                                            {ligne.debut && ligne.fin
                                                ? `${dateLongue(ligne.debut)} → ${dateLongue(ligne.fin)}`
                                                : '—'}
                                        </td>
                                        <td data-label="Montant">
                                            {ligne.origine === 'OFFERT' ? (
                                                <span className="chip chip-green">
                                                    Offert par DAG
                                                </span>
                                            ) : (
                                                formatMontant(ligne.montant)
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {paiementOuvert && factureEnAttente && (
                <PayerFactureModal
                    facture={{
                        id: factureEnAttente.id,
                        reference: factureEnAttente.reference,
                        objet: factureEnAttente.objet,
                        solde: factureEnAttente.solde,
                    }}
                    moyens={moyensPaiement}
                    onClose={() => setPaiementOuvert(false)}
                />
            )}
        </>
    );
}
