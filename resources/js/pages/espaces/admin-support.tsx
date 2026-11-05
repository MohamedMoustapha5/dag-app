import { Head, Link } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    formaterDateSupport,
    SUPPORT_STATUT_CHIP,
} from '@/components/espaces/support/support-format';
import supportAdmin from '@/routes/espace-admin/support';

type Ticket = {
    id: string;
    reference: string;
    nom: string;
    provenance: string;
    categorie: string;
    sujet: string;
    statut: string;
    dernierMessage: string | null;
};

type Props = {
    tickets: Ticket[];
};

const FILTRES = [
    { value: 'OUVERT', label: 'À traiter' },
    { value: 'REPONDU', label: 'En attente du demandeur' },
    { value: 'CLOS', label: 'Clôturés' },
    { value: 'TOUS', label: 'Tous' },
] as const;

const STATUT_LABEL: Record<string, string> = {
    OUVERT: 'À traiter',
    REPONDU: 'Répondu',
    CLOS: 'Clôturé',
};

export default function AdminSupport({ tickets }: Props) {
    const [filtre, setFiltre] =
        useState<(typeof FILTRES)[number]['value']>('OUVERT');

    const affiches = useMemo(
        () =>
            filtre === 'TOUS'
                ? tickets
                : tickets.filter((ticket) => ticket.statut === filtre),
        [tickets, filtre],
    );

    return (
        <>
            <Head title="Support — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/phone-call-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Support</h1>
                    <p>
                        Questions des clients et apprenants depuis leur espace,
                        et messages du formulaire de contact du site.
                    </p>
                </div>
            </div>

            <div className="status-tabs">
                {FILTRES.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        className={`pill${filtre === item.value ? ' active' : ''}`}
                        onClick={() => setFiltre(item.value)}
                    >
                        {item.label}
                        {item.value !== 'TOUS' &&
                            ` (${tickets.filter((ticket) => ticket.statut === item.value).length})`}
                    </button>
                ))}
            </div>

            <div className="data-table-wrap">
                {affiches.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune demande dans cette catégorie.
                    </p>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Demandeur</th>
                                <th>Objet</th>
                                <th>Statut</th>
                                <th>Dernier message</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {affiches.map((ticket) => (
                                <tr key={ticket.id}>
                                    <td>
                                        <span style={{ fontWeight: 600 }}>
                                            {ticket.nom}
                                        </span>
                                        <br />
                                        <span
                                            className="muted"
                                            style={{ fontSize: 12 }}
                                        >
                                            {ticket.provenance}
                                        </span>
                                    </td>
                                    <td>
                                        {ticket.sujet}
                                        <br />
                                        <span
                                            className="muted"
                                            style={{ fontSize: 12 }}
                                        >
                                            {ticket.reference} ·{' '}
                                            {ticket.categorie}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${SUPPORT_STATUT_CHIP[ticket.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[ticket.statut] ??
                                                ticket.statut}
                                        </span>
                                    </td>
                                    <td className="muted">
                                        {formaterDateSupport(
                                            ticket.dernierMessage,
                                        )}
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        <Link
                                            href={supportAdmin.show(ticket.id)}
                                            className="link-action"
                                            style={{
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                gap: 6,
                                            }}
                                        >
                                            <MessageSquare size={14} />
                                            Ouvrir
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}
