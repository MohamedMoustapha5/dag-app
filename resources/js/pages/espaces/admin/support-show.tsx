import { router } from '@inertiajs/react';
import SupportFil, {
    type MessageFil,
} from '@/components/espaces/support/support-fil';
import supportAdmin from '@/routes/espace-admin/support';

type Props = {
    peutRepondre: boolean;
    ticket: {
        id: string;
        reference: string;
        nom: string;
        email: string;
        telephone: string | null;
        provenance: string;
        categorie: string;
        sujet: string;
        statut: string;
    };
    messages: MessageFil[];
};

const STATUT_LABEL: Record<string, string> = {
    OUVERT: 'À traiter',
    REPONDU: 'Répondu',
    CLOS: 'Clôturé',
};

export default function AdminSupportShow({
    peutRepondre,
    ticket,
    messages,
}: Props) {
    const changerStatut = (statut: 'OUVERT' | 'CLOS') =>
        router.patch(
            supportAdmin.update(ticket.id).url,
            { statut },
            { preserveScroll: true },
        );

    return (
        <SupportFil
            ticket={{ ...ticket, statutLabel: STATUT_LABEL[ticket.statut] }}
            messages={messages}
            cote="equipe"
            titrePage={`Support ${ticket.reference} — Administration`}
            lienRetour={{
                href: supportAdmin.index().url,
                label: 'Toutes les demandes de support',
            }}
            urlRepondre={
                peutRepondre ? supportAdmin.repondre(ticket.id).url : null
            }
            actions={
                peutRepondre && (
                    <button
                        type="button"
                        className="btn-ghost"
                        onClick={() =>
                            changerStatut(
                                ticket.statut === 'CLOS' ? 'OUVERT' : 'CLOS',
                            )
                        }
                    >
                        {ticket.statut === 'CLOS' ? 'Rouvrir' : 'Clôturer'}
                    </button>
                )
            }
            encart={
                <div
                    className="card"
                    style={{
                        padding: 20,
                        marginBottom: 20,
                        display: 'flex',
                        gap: 32,
                        flexWrap: 'wrap',
                        fontSize: 13.5,
                    }}
                >
                    <div>
                        <p className="muted" style={{ fontSize: 12 }}>
                            Demandeur
                        </p>
                        <p style={{ fontWeight: 600 }}>{ticket.nom}</p>
                        <p className="muted" style={{ fontSize: 12 }}>
                            {ticket.provenance}
                        </p>
                    </div>
                    <div>
                        <p className="muted" style={{ fontSize: 12 }}>
                            E-mail
                        </p>
                        <a
                            href={`mailto:${ticket.email}`}
                            className="link-action"
                        >
                            {ticket.email}
                        </a>
                    </div>
                    {ticket.telephone && (
                        <div>
                            <p className="muted" style={{ fontSize: 12 }}>
                                Téléphone
                            </p>
                            <p>{ticket.telephone}</p>
                        </div>
                    )}
                </div>
            }
        />
    );
}
