import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import apprenantsAdmin from '@/routes/espace-admin/apprenants';
import clientsAdmin from '@/routes/espace-admin/clients';

type Client = {
    id: string;
    nom: string;
    email: string;
    type: string;
    typeLabel: string;
    ville: string | null;
    telephone: string | null;
    statut: string;
    finAccesRecrutement: string | null;
    partenaireOfficiel: boolean;
    demandesCount: number;
    inscritLe: string | null;
};

type ApprenantCompte = {
    id: string;
    nom: string;
    email: string;
    telephone: string | null;
    ville: string | null;
    estForme: boolean;
    inscriptionsCount: number;
    inscritLe: string | null;
};

type Props = {
    clients: Client[];
    apprenants: ApprenantCompte[] | null;
    actions: {
        supprimer: boolean;
        supprimerApprenant: boolean;
        offrirAcces: boolean;
    };
};

const DUREES_OFFERTES = [
    { mois: 1, libelle: '1 mois' },
    { mois: 3, libelle: '3 mois' },
    { mois: 6, libelle: '6 mois' },
    { mois: 12, libelle: '1 an' },
];

function dateCourte(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR');
}

export default function AdminClients({ clients, apprenants, actions }: Props) {
    const [onglet, setOnglet] = useState<'clients' | 'apprenants'>('clients');
    const [offreClientId, setOffreClientId] = useState<string | null>(null);
    const [offreMois, setOffreMois] = useState(1);
    const [offreEnCours, setOffreEnCours] = useState(false);

    const supprimerApprenant = (apprenant: ApprenantCompte) => {
        if (
            !confirm(
                `Supprimer le compte de « ${apprenant.nom} » ? Son dossier, ses inscriptions, candidatures et attestations seront supprimés définitivement. Ses factures et contrats existants sont conservés.`,
            )
        ) {
            return;
        }
        router.delete(apprenantsAdmin.destroy(apprenant.id).url, {
            preserveScroll: true,
        });
    };

    const offrirAcces = (client: Client) => {
        router.post(
            clientsAdmin.accesRecrutement(client.id).url,
            { mois: offreMois },
            {
                preserveScroll: true,
                onStart: () => setOffreEnCours(true),
                onFinish: () => setOffreEnCours(false),
                onSuccess: () => setOffreClientId(null),
            },
        );
    };

    const supprimer = (client: Client) => {
        if (
            !confirm(
                `Supprimer le compte de « ${client.nom} » ? Cette action est définitive. Ses demandes, contrats et factures existants seront conservés mais ne seront plus rattachés à un compte client.`,
            )
        ) {
            return;
        }
        router.delete(clientsAdmin.destroy(client.id).url);
    };

    return (
        <>
            <Head title="Comptes clients — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/team-meeting-laptop.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Comptes clients</h1>
                    <p>
                        {clients.length} compte
                        {clients.length > 1 ? 's' : ''} client
                        {clients.length > 1 ? 's' : ''}
                        {apprenants !== null &&
                            ` et ${apprenants.length} compte${apprenants.length > 1 ? 's' : ''} apprenant${apprenants.length > 1 ? 's' : ''}`}
                        .
                    </p>
                </div>
            </div>

            {apprenants !== null && (
                <div style={{ display: 'flex', gap: 10, marginBottom: 16 }}>
                    <button
                        type="button"
                        className={
                            onglet === 'clients' ? 'btn-primary' : 'btn-outline'
                        }
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        onClick={() => setOnglet('clients')}
                    >
                        Clients ({clients.length})
                    </button>
                    <button
                        type="button"
                        className={
                            onglet === 'apprenants'
                                ? 'btn-primary'
                                : 'btn-outline'
                        }
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        onClick={() => setOnglet('apprenants')}
                    >
                        Apprenants ({apprenants.length})
                    </button>
                </div>
            )}

            {onglet === 'apprenants' && apprenants !== null && (
                <div className="card" style={{ padding: 24 }}>
                    {apprenants.length === 0 ? (
                        <p className="muted" style={{ fontSize: 13 }}>
                            Aucun apprenant n'a encore créé son compte.
                        </p>
                    ) : (
                        <div className="data-table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Apprenant</th>
                                        <th>Ville</th>
                                        <th>Profil</th>
                                        <th>Inscriptions</th>
                                        <th>Inscrit le</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {apprenants.map((apprenant) => (
                                        <tr key={apprenant.id}>
                                            <td>
                                                <Link
                                                    href={apprenantsAdmin.show(
                                                        apprenant.id,
                                                    )}
                                                    className="link-action"
                                                >
                                                    {apprenant.nom}
                                                </Link>
                                                <p
                                                    className="muted"
                                                    style={{ fontSize: 12.5 }}
                                                >
                                                    {apprenant.email}
                                                    {apprenant.telephone
                                                        ? ` · ${apprenant.telephone}`
                                                        : ''}
                                                </p>
                                            </td>
                                            <td className="muted">
                                                {apprenant.ville ?? '—'}
                                            </td>
                                            <td>
                                                <span
                                                    className={`chip ${apprenant.estForme ? 'chip-green' : 'chip-gray'}`}
                                                >
                                                    {apprenant.estForme
                                                        ? 'Formé'
                                                        : 'En parcours'}
                                                </span>
                                            </td>
                                            <td className="muted">
                                                {apprenant.inscriptionsCount}
                                            </td>
                                            <td className="muted">
                                                {apprenant.inscritLe ?? '—'}
                                            </td>
                                            <td style={{ textAlign: 'right' }}>
                                                {actions.supprimerApprenant && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            supprimerApprenant(
                                                                apprenant,
                                                            )
                                                        }
                                                        className="link-action"
                                                        style={{
                                                            color: 'var(--dag-red)',
                                                        }}
                                                    >
                                                        Supprimer
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            <div
                className="card"
                style={{
                    padding: 24,
                    display: onglet === 'clients' ? undefined : 'none',
                }}
            >
                {clients.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun compte client pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Ville</th>
                                    <th>Statut</th>
                                    <th>Demandes</th>
                                    <th>Inscrit le</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {clients.map((client) => (
                                    <tr key={client.id}>
                                        <td>
                                            <p style={{ fontWeight: 600 }}>
                                                {client.nom}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12.5 }}
                                            >
                                                {client.email}
                                                {client.telephone
                                                    ? ` · ${client.telephone}`
                                                    : ''}
                                            </p>
                                        </td>
                                        <td className="muted">
                                            {client.typeLabel}
                                            {client.partenaireOfficiel && (
                                                <span
                                                    className="chip chip-blue"
                                                    style={{ marginLeft: 6 }}
                                                >
                                                    Partenaire officiel
                                                </span>
                                            )}
                                            {client.finAccesRecrutement && (
                                                <span
                                                    className="chip chip-green"
                                                    style={{ marginLeft: 6 }}
                                                >
                                                    Recrutement jusqu’au{' '}
                                                    {dateCourte(
                                                        client.finAccesRecrutement,
                                                    )}
                                                </span>
                                            )}
                                        </td>
                                        <td className="muted">
                                            {client.ville ?? '—'}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[client.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[client.statut] ??
                                                    client.statut}
                                            </span>
                                        </td>
                                        <td className="muted">
                                            {client.demandesCount}
                                        </td>
                                        <td className="muted">
                                            {client.inscritLe ?? '—'}
                                        </td>
                                        <td
                                            style={{
                                                textAlign: 'right',
                                                whiteSpace: 'nowrap',
                                            }}
                                        >
                                            {actions.offrirAcces &&
                                                (client.type === 'ENTREPRISE' ||
                                                    client.type ===
                                                        'ORGANISATION') &&
                                                (offreClientId === client.id ? (
                                                    <span
                                                        style={{
                                                            display:
                                                                'inline-flex',
                                                            alignItems:
                                                                'center',
                                                            gap: 8,
                                                            marginRight: 14,
                                                        }}
                                                    >
                                                        <select
                                                            aria-label="Durée offerte"
                                                            value={offreMois}
                                                            onChange={(event) =>
                                                                setOffreMois(
                                                                    Number(
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    ),
                                                                )
                                                            }
                                                            style={{
                                                                width: 'auto',
                                                                padding:
                                                                    '5px 8px',
                                                            }}
                                                        >
                                                            {DUREES_OFFERTES.map(
                                                                (duree) => (
                                                                    <option
                                                                        key={
                                                                            duree.mois
                                                                        }
                                                                        value={
                                                                            duree.mois
                                                                        }
                                                                    >
                                                                        {
                                                                            duree.libelle
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            disabled={
                                                                offreEnCours
                                                            }
                                                            onClick={() =>
                                                                offrirAcces(
                                                                    client,
                                                                )
                                                            }
                                                        >
                                                            Offrir
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            style={{
                                                                color: 'var(--dag-text-muted)',
                                                            }}
                                                            onClick={() =>
                                                                setOffreClientId(
                                                                    null,
                                                                )
                                                            }
                                                        >
                                                            Annuler
                                                        </button>
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setOffreMois(1);
                                                            setOffreClientId(
                                                                client.id,
                                                            );
                                                        }}
                                                        className="link-action"
                                                        style={{
                                                            marginRight: 14,
                                                        }}
                                                    >
                                                        Offrir un accès
                                                    </button>
                                                ))}
                                            {actions.supprimer && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        supprimer(client)
                                                    }
                                                    className="link-action"
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                    }}
                                                >
                                                    Supprimer
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
