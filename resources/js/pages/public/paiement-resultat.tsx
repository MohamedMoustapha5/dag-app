import { Head, Link, usePoll } from '@inertiajs/react';
import {
    CircleAlert,
    CircleCheck,
    CircleX,
    Clock,
    ShieldCheck,
} from 'lucide-react';
import { useEffect, useState } from 'react';

type Statut = 'CONFIRME' | 'ECHEC' | 'EN_ATTENTE' | 'A_VERIFIER';

type Props = {
    paiement: {
        montant: string;
        moyen: string | null;
        reference: string | null;
        facture: string;
        statut: Statut;
    };
    retour: string;
};

const NAVY = '#071f41';
const BLUE = '#0166fe';
const MUTED = '#5b6b82';

/** How often a pending payment is checked again, and for how long. */
const INTERVALLE_MS = 4000;
const DUREE_MAX_MS = 120000;

const ECRANS: Record<
    Statut,
    {
        titre: string;
        texte: string;
        couleur: string;
        fond: string;
        Icone: typeof CircleCheck;
        action: string;
    }
> = {
    CONFIRME: {
        titre: 'Paiement réussi',
        texte: 'Votre paiement est confirmé et votre facture est mise à jour. Un e-mail de confirmation vous a été envoyé ; le reçu est disponible dans votre espace.',
        couleur: '#0f8a4b',
        fond: '#e3f6ec',
        Icone: CircleCheck,
        action: 'Voir mes factures',
    },
    ECHEC: {
        titre: 'Paiement échoué',
        texte: "Le paiement a échoué, a expiré ou a été annulé. Aucun montant n'a été crédité : vous pouvez réessayer depuis vos factures.",
        couleur: '#c0262d',
        fond: '#fde8e8',
        Icone: CircleX,
        action: 'Réessayer le paiement',
    },
    EN_ATTENTE: {
        titre: 'Paiement en cours de confirmation',
        texte: 'Validez la demande sur votre téléphone si ce n’est pas déjà fait. Cette page se met à jour toute seule dès que l’opérateur confirme.',
        couleur: '#8a5a06',
        fond: '#fdf3b3',
        Icone: Clock,
        action: 'Retour à mes factures',
    },
    A_VERIFIER: {
        titre: 'Paiement à vérifier',
        texte: "Le montant reçu ne correspond pas à la facture : l'équipe DAG va vérifier votre paiement et revenir vers vous.",
        couleur: '#8a5a06',
        fond: '#fdf3b3',
        Icone: CircleAlert,
        action: 'Retour à mes factures',
    },
};

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
            <strong
                style={{
                    color: NAVY,
                    textAlign: 'right',
                    overflowWrap: 'anywhere',
                }}
            >
                {valeur}
            </strong>
        </div>
    );
}

export default function PaiementResultat({ paiement, retour }: Props) {
    const enAttente = paiement.statut === 'EN_ATTENTE';
    const [delaiDepasse, setDelaiDepasse] = useState(false);
    const { stop } = usePoll(
        INTERVALLE_MS,
        { only: ['paiement'] },
        { autoStart: enAttente },
    );

    useEffect(() => {
        if (!enAttente) {
            stop();

            return;
        }

        const minuterie = window.setTimeout(() => {
            stop();
            setDelaiDepasse(true);
        }, DUREE_MAX_MS);

        return () => window.clearTimeout(minuterie);
    }, [enAttente, stop]);

    const ecran = ECRANS[paiement.statut];
    const montant = Number(paiement.montant).toLocaleString('fr-FR');

    return (
        <>
            <Head title={ecran.titre} />

            <main
                style={{
                    minHeight: '100vh',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 18,
                    padding: '32px 16px',
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
                        padding: '30px 28px 26px',
                        textAlign: 'center',
                    }}
                >
                    <div
                        aria-hidden
                        style={{
                            width: 68,
                            height: 68,
                            borderRadius: '50%',
                            margin: '0 auto 16px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            background: ecran.fond,
                            color: ecran.couleur,
                        }}
                    >
                        <ecran.Icone size={36} />
                    </div>

                    <h1
                        style={{
                            fontSize: 22,
                            fontWeight: 800,
                            color: NAVY,
                            marginBottom: 8,
                        }}
                    >
                        {ecran.titre}
                    </h1>
                    <p
                        style={{
                            fontSize: 30,
                            fontWeight: 800,
                            color: NAVY,
                            marginBottom: 10,
                        }}
                    >
                        {montant}{' '}
                        <span style={{ fontSize: 15, fontWeight: 700 }}>
                            FCFA
                        </span>
                    </p>
                    <p
                        role="status"
                        style={{
                            fontSize: 13.5,
                            lineHeight: 1.6,
                            color: MUTED,
                            marginBottom: 18,
                        }}
                    >
                        {enAttente && delaiDepasse
                            ? 'La confirmation prend plus de temps que prévu. Votre facture sera mise à jour automatiquement et vous recevrez un e-mail dès la validation.'
                            : ecran.texte}
                    </p>

                    <div style={{ textAlign: 'left', marginBottom: 22 }}>
                        <Ligne libelle="Facture" valeur={paiement.facture} />
                        <Ligne
                            libelle="Moyen de paiement"
                            valeur={paiement.moyen ?? '—'}
                        />
                        <Ligne
                            libelle="Référence de transaction"
                            valeur={paiement.reference ?? '—'}
                        />
                    </div>

                    <Link
                        href={retour}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '13px 16px',
                            borderRadius: 12,
                            fontSize: 14,
                            fontWeight: 700,
                            background: BLUE,
                            color: '#fff',
                            textDecoration: 'none',
                            boxShadow: '0 8px 20px rgba(1,102,254,0.28)',
                        }}
                    >
                        {ecran.action}
                    </Link>
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
