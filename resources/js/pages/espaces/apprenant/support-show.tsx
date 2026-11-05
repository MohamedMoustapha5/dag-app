import SupportFil, {
    BoutonClore,
    type MessageFil,
} from '@/components/espaces/support/support-fil';

type Props = {
    ticket: {
        reference: string;
        sujet: string;
        categorie: string;
        statut: string;
        statutLabel: string;
    };
    messages: MessageFil[];
    urls: { liste: string; repondre: string; clore: string };
};

export default function SupportShow({ ticket, messages, urls }: Props) {
    return (
        <SupportFil
            ticket={ticket}
            messages={messages}
            cote="demandeur"
            titrePage={`Support ${ticket.reference}`}
            lienRetour={{ href: urls.liste, label: 'Mes demandes de support' }}
            urlRepondre={urls.repondre}
            actions={
                ticket.statut !== 'CLOS' && <BoutonClore url={urls.clore} />
            }
        />
    );
}
