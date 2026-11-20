import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    Inbox,
    Search,
    UserRoundCheck,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { ProchaineEtapeCourte } from '@/components/espaces/suivi-procedure';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import { formatMontant } from '@/lib/montant';
import contratsAdmin from '@/routes/espace-admin/contrats';
import demandesAdmin from '@/routes/espace-admin/demandes';

type Demande = {
    id: string;
    reference: string;
    client: string;
    objet: string;
    statut: string;
    montantNegocie: string | null;
    aContrat: boolean;
    contratStatut: string | null;
    contratId: string | null;
    payee: boolean;
    createdAt: string | null;
    updatedAt: string | null;
    prochaineEtape: {
        titre: string;
        aVous: boolean;
        libelleActeur: string;
    } | null;
};

type Onglet =
    | 'nouvelles'
    | 'a_traiter'
    | 'attente_client'
    | 'cloturees'
    | 'refusees'
    | 'toutes';

type Props = {
    demandes: Demande[];
    peutModifier: boolean;
    peutEnvoyerDevis: boolean;
    peutGenererContrat: boolean;
    peutEnvoyerContrat: boolean;
};

const STATUTS_DEVIS = ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE'];

/** A demande waiting on the client for longer than this is worth a reminder. */
const JOURS_AVANT_RELANCE = 7;

/** A demande received less than this many days ago is flagged as new. */
const JOURS_NOUVEAUTE = 2;

const ONGLETS: {
    cle: Onglet;
    libelle: string;
    aide: string;
    vide: string;
}[] = [
    {
        cle: 'nouvelles',
        libelle: 'Nouvelles',
        aide: 'Demandes reçues, pas encore chiffrées : étudiez-les et envoyez le devis, le contrat part avec.',
        vide: 'Aucune nouvelle demande : tout a été étudié.',
    },
    {
        cle: 'a_traiter',
        libelle: 'À traiter par DAG',
        aide: 'Demandes en cours où DAG doit agir : valider un contrat signé, démarrer, livrer ou clôturer la prestation.',
        vide: 'Rien à traiter : toutes les demandes en cours sont à jour.',
    },
    {
        cle: 'attente_client',
        libelle: 'En attente du client',
        aide: 'Le client doit signer son contrat ou régler sa facture. Les plus anciennes sont en tête : relancez celles qui attendent depuis plus d’une semaine.',
        vide: 'Aucune demande n’attend le client.',
    },
    {
        cle: 'cloturees',
        libelle: 'Clôturées',
        aide: 'Prestations livrées et demandes terminées.',
        vide: 'Aucune demande clôturée pour le moment.',
    },
    {
        cle: 'refusees',
        libelle: 'Refusées',
        aide: 'Demandes que DAG n’a pas retenues.',
        vide: 'Aucune demande refusée.',
    },
    {
        cle: 'toutes',
        libelle: 'Toutes',
        aide: 'Toutes les demandes, les plus récemment mises à jour en tête.',
        vide: 'Aucune demande pour le moment.',
    },
];

/** Where a demande belongs in the page: every demande is in exactly one tab. */
function ongletDe(demande: Demande): Exclude<Onglet, 'toutes'> {
    if (demande.statut === 'REFUSEE') {
        return 'refusees';
    }

    if (demande.statut === 'TERMINEE') {
        return 'cloturees';
    }

    if (['BROUILLON', 'EN_ETUDE'].includes(demande.statut)) {
        return 'nouvelles';
    }

    return demande.prochaineEtape !== null && !demande.prochaineEtape.aVous
        ? 'attente_client'
        : 'a_traiter';
}

function joursDepuis(date: string | null): number | null {
    if (date === null) {
        return null;
    }

    const [annee, mois, jour] = date.split('-').map(Number);
    const debut = new Date(annee, mois - 1, jour).getTime();
    const aujourdhui = new Date();
    aujourdhui.setHours(0, 0, 0, 0);

    return Math.max(0, Math.round((aujourdhui.getTime() - debut) / 86_400_000));
}

function depuis(jours: number | null): string {
    if (jours === null) {
        return '—';
    }

    if (jours === 0) {
        return 'aujourd’hui';
    }

    return jours === 1 ? 'hier' : `il y a ${jours} jours`;
}

function dateCourte(date: string | null): string {
    if (date === null) {
        return '—';
    }

    const [annee, mois, jour] = date.split('-').map(Number);

    return new Date(annee, mois - 1, jour).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function ongletInitial(compte: (onglet: Onglet) => number): Onglet {
    try {
        const demande = new URLSearchParams(window.location.search).get(
            'onglet',
        );

        if (ONGLETS.some((onglet) => onglet.cle === demande)) {
            return demande as Onglet;
        }
    } catch {
        // No window (server rendering): fall through to the default.
    }

    if (compte('nouvelles') > 0) {
        return 'nouvelles';
    }

    return compte('a_traiter') > 0 ? 'a_traiter' : 'toutes';
}

export default function AdminDemandes({
    demandes,
    peutEnvoyerDevis,
    peutGenererContrat,
    peutEnvoyerContrat,
}: Props) {
    const [devisPour, setDevisPour] = useState<Demande | null>(null);
    const [recherche, setRecherche] = useState('');

    const compte = (onglet: Onglet) =>
        onglet === 'toutes'
            ? demandes.length
            : demandes.filter((demande) => ongletDe(demande) === onglet).length;

    const [onglet, setOnglet] = useState<Onglet>(() => ongletInitial(compte));

    const choisirOnglet = (cle: Onglet) => {
        setOnglet(cle);

        // Kept in the address so coming back from a demande reopens the tab.
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('onglet', cle);
            window.history.replaceState(window.history.state, '', url);
        } catch {
            // Address not writable: the tab simply is not remembered.
        }
    };

    const terme = recherche.trim().toLowerCase();
    const demandesAffichees = demandes
        .filter(
            (demande) => onglet === 'toutes' || ongletDe(demande) === onglet,
        )
        .filter(
            (demande) =>
                terme === '' ||
                [demande.client, demande.objet, demande.reference].some(
                    (valeur) => valeur.toLowerCase().includes(terme),
                ),
        )
        .sort((a, b) => {
            // Waiting on the client: the longest wait first, to remind them.
            if (onglet === 'attente_client') {
                return (a.updatedAt ?? '').localeCompare(b.updatedAt ?? '');
            }

            const cle = onglet === 'nouvelles' ? 'createdAt' : 'updatedAt';

            return (b[cle] ?? '').localeCompare(a[cle] ?? '');
        });

    const ongletCourant = ONGLETS.find((item) => item.cle === onglet)!;
    const termine = onglet === 'cloturees' || onglet === 'refusees';

    const envoyerContrat = (contratId: string) => {
        router.patch(
            contratsAdmin.update(contratId).url,
            { statut: 'ENVOYE' },
            { preserveScroll: true },
        );
    };

    const genererContrat = (demande: Demande) => {
        router.post(contratsAdmin.store().url, { demande_id: demande.id });
    };

    const resume: {
        cle: Onglet;
        libelle: string;
        icone: typeof Inbox;
        ton: string;
    }[] = [
        { cle: 'nouvelles', libelle: 'Nouvelles', icone: Inbox, ton: 'bleu' },
        {
            cle: 'a_traiter',
            libelle: 'À traiter par DAG',
            icone: Clock,
            ton: 'orange',
        },
        {
            cle: 'attente_client',
            libelle: 'En attente du client',
            icone: UserRoundCheck,
            ton: 'gris',
        },
        {
            cle: 'cloturees',
            libelle: 'Clôturées',
            icone: CheckCircle2,
            ton: 'vert',
        },
    ];

    return (
        <>
            <Head title="Demandes clients — Administration" />

            <div style={{ marginBottom: 20 }}>
                <h1 style={{ fontSize: 26, color: 'var(--dag-navy)' }}>
                    Demandes clients
                </h1>
                <p
                    className="muted"
                    style={{ marginTop: 6, fontSize: 14, lineHeight: 1.6 }}
                >
                    Devis et contrat envoyés ensemble, signature du client,
                    validation du contrat par DAG, paiement, puis réalisation.
                </p>
            </div>

            <div className="demandes-resume">
                {resume.map(({ cle, libelle, icone: Icone, ton }) => (
                    <button
                        key={cle}
                        type="button"
                        className={`demandes-resume-carte ${ton}${onglet === cle ? ' active' : ''}`}
                        aria-pressed={onglet === cle}
                        onClick={() => choisirOnglet(cle)}
                    >
                        <span
                            className="demandes-resume-icone"
                            aria-hidden="true"
                        >
                            <Icone size={18} />
                        </span>
                        <span>
                            <strong>{compte(cle)}</strong>
                            <span>{libelle}</span>
                        </span>
                    </button>
                ))}
            </div>

            <div className="demandes-barre">
                <div
                    className="account-tabs"
                    role="group"
                    aria-label="Filtrer les demandes"
                >
                    {ONGLETS.map((item) => (
                        <button
                            key={item.cle}
                            type="button"
                            aria-pressed={onglet === item.cle}
                            className={`account-tab${onglet === item.cle ? ' active' : ''}`}
                            onClick={() => choisirOnglet(item.cle)}
                        >
                            {item.libelle} ({compte(item.cle)})
                        </button>
                    ))}
                </div>
                <label className="demandes-recherche">
                    <Search size={15} aria-hidden="true" />
                    <span className="sr-only">Rechercher une demande</span>
                    <input
                        type="search"
                        placeholder="Client, objet ou référence"
                        value={recherche}
                        onChange={(event) => setRecherche(event.target.value)}
                    />
                </label>
            </div>

            <p className="demandes-aide">{ongletCourant.aide}</p>

            <div className="card" style={{ padding: 24 }}>
                {demandesAffichees.length === 0 ? (
                    <p className="data-table-empty">
                        {terme !== ''
                            ? `Aucune demande ne correspond à « ${recherche.trim()} » dans cet onglet.`
                            : ongletCourant.vide}
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Demande</th>
                                    <th>Client</th>
                                    <th>
                                        {onglet === 'nouvelles'
                                            ? 'Reçue'
                                            : termine
                                              ? 'Clôturée le'
                                              : 'Dernière action'}
                                    </th>
                                    <th>Statut</th>
                                    {!termine && <th>Prochaine étape</th>}
                                    {onglet !== 'nouvelles' && <th>Contrat</th>}
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {demandesAffichees.map((demande) => {
                                    const recue = joursDepuis(
                                        demande.createdAt,
                                    );
                                    const attente = joursDepuis(
                                        demande.updatedAt,
                                    );
                                    const nouvelle =
                                        ongletDe(demande) === 'nouvelles' &&
                                        recue !== null &&
                                        recue < JOURS_NOUVEAUTE;
                                    const aRelancer =
                                        ongletDe(demande) ===
                                            'attente_client' &&
                                        attente !== null &&
                                        attente >= JOURS_AVANT_RELANCE;

                                    return (
                                        <tr key={demande.id}>
                                            <td
                                                className="cellule-titre"
                                                data-label="Demande"
                                            >
                                                <Link
                                                    href={demandesAdmin.show(
                                                        demande.id,
                                                    )}
                                                    style={{
                                                        fontWeight: 600,
                                                        color: 'var(--dag-navy)',
                                                    }}
                                                >
                                                    {demande.objet}
                                                </Link>
                                                {nouvelle && (
                                                    <span className="demande-nouvelle">
                                                        Nouveau
                                                    </span>
                                                )}
                                                <span
                                                    className="muted"
                                                    style={{
                                                        display: 'block',
                                                        fontSize: 12,
                                                    }}
                                                >
                                                    {demande.reference}
                                                    {demande.montantNegocie &&
                                                        ` · ${formatMontant(demande.montantNegocie)}`}
                                                </span>
                                            </td>
                                            <td
                                                data-label="Client"
                                                style={{ fontWeight: 600 }}
                                            >
                                                {demande.client}
                                            </td>
                                            <td
                                                data-label={
                                                    onglet === 'nouvelles'
                                                        ? 'Reçue'
                                                        : termine
                                                          ? 'Clôturée le'
                                                          : 'Dernière action'
                                                }
                                            >
                                                {termine ? (
                                                    dateCourte(
                                                        demande.updatedAt,
                                                    )
                                                ) : (
                                                    <span
                                                        title={dateCourte(
                                                            onglet ===
                                                                'nouvelles'
                                                                ? demande.createdAt
                                                                : demande.updatedAt,
                                                        )}
                                                    >
                                                        {depuis(
                                                            onglet ===
                                                                'nouvelles'
                                                                ? recue
                                                                : attente,
                                                        )}
                                                    </span>
                                                )}
                                                {aRelancer && (
                                                    <span className="demande-relance">
                                                        À relancer
                                                    </span>
                                                )}
                                            </td>
                                            <td data-label="Statut">
                                                <span
                                                    className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                                                >
                                                    {STATUT_LABEL[
                                                        demande.statut
                                                    ] ?? demande.statut}
                                                </span>
                                            </td>
                                            {!termine && (
                                                <td data-label="Prochaine étape">
                                                    <ProchaineEtapeCourte
                                                        prochaine={
                                                            demande.prochaineEtape
                                                        }
                                                    />
                                                </td>
                                            )}
                                            {onglet !== 'nouvelles' && (
                                                <td data-label="Contrat">
                                                    <EtatContrat
                                                        demande={demande}
                                                    />
                                                </td>
                                            )}
                                            <td
                                                data-label=""
                                                className="cellule-actions"
                                            >
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 6,
                                                        flexDirection: 'column',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    {peutEnvoyerDevis &&
                                                        STATUTS_DEVIS.includes(
                                                            demande.statut,
                                                        ) && (
                                                            <button
                                                                type="button"
                                                                className={
                                                                    demande.statut ===
                                                                    'DEVIS_ENVOYE'
                                                                        ? 'link-action'
                                                                        : 'btn-primary btn-petit'
                                                                }
                                                                onClick={() =>
                                                                    setDevisPour(
                                                                        demande,
                                                                    )
                                                                }
                                                            >
                                                                {demande.statut ===
                                                                'DEVIS_ENVOYE'
                                                                    ? 'Renvoyer un devis'
                                                                    : 'Envoyer le devis'}
                                                            </button>
                                                        )}
                                                    <Link
                                                        href={demandesAdmin.show(
                                                            demande.id,
                                                        )}
                                                        className="link-action"
                                                    >
                                                        Voir
                                                    </Link>
                                                    {peutGenererContrat &&
                                                        !STATUTS_DEVIS.includes(
                                                            demande.statut,
                                                        ) &&
                                                        demande.statut !==
                                                            'REFUSEE' &&
                                                        !demande.aContrat && (
                                                            <button
                                                                type="button"
                                                                className="link-action"
                                                                onClick={() =>
                                                                    genererContrat(
                                                                        demande,
                                                                    )
                                                                }
                                                            >
                                                                Générer le
                                                                contrat
                                                            </button>
                                                        )}
                                                    {demande.contratId && (
                                                        <Link
                                                            href={contratsAdmin.show(
                                                                demande.contratId,
                                                            )}
                                                            className="link-action"
                                                        >
                                                            Voir le contrat
                                                        </Link>
                                                    )}
                                                    {demande.contratId &&
                                                        demande.contratStatut ===
                                                            'BROUILLON' &&
                                                        peutEnvoyerContrat && (
                                                            <button
                                                                type="button"
                                                                className="link-action"
                                                                onClick={() =>
                                                                    envoyerContrat(
                                                                        demande.contratId as string,
                                                                    )
                                                                }
                                                            >
                                                                Envoyer le
                                                                contrat
                                                            </button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {devisPour && (
                <DevisModal
                    demande={devisPour}
                    onClose={() => setDevisPour(null)}
                />
            )}
        </>
    );
}

/**
 * The contract is sent with the devis and signed before payment: this shows
 * where it stands so no demande is paid or started without one.
 */
function EtatContrat({ demande }: { demande: Demande }) {
    if (
        !demande.aContrat &&
        ['BROUILLON', 'EN_ETUDE', 'REFUSEE'].includes(demande.statut)
    ) {
        return <span className="muted">—</span>;
    }

    const etats: Record<string, { label: string; chip: string }> = {
        BROUILLON: { label: 'À envoyer', chip: 'chip-orange' },
        ENVOYE: { label: 'En attente de signature', chip: 'chip-orange' },
        SIGNE: { label: 'Signé, à valider', chip: 'chip-orange' },
        ACTIF: { label: 'Validé', chip: 'chip-green' },
        TERMINE: { label: 'Terminé', chip: 'chip-green' },
    };
    const etat =
        demande.contratStatut !== null
            ? (etats[demande.contratStatut] ?? {
                  label: demande.contratStatut,
                  chip: 'chip-gray',
              })
            : { label: 'À générer', chip: 'chip-red' };

    return <span className={`chip ${etat.chip}`}>{etat.label}</span>;
}

function DevisModal({
    demande,
    onClose,
}: {
    demande: Demande;
    onClose: () => void;
}) {
    const form = useForm({
        montant: demande.montantNegocie
            ? String(Math.round(Number(demande.montantNegocie)))
            : '',
        message: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(demandesAdmin.devis(demande.id).url, {
            onSuccess: onClose,
        });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Envoyer un devis"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Envoyer un devis
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 24,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Le client reçoit le montant proposé et peut payer
                    directement depuis son espace.
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="devis-client">Client</label>
                        <input
                            id="devis-client"
                            value={demande.client}
                            readOnly
                        />
                    </div>
                    <div>
                        <label htmlFor="devis-service">Service</label>
                        <input
                            id="devis-service"
                            value={demande.objet}
                            readOnly
                        />
                    </div>
                    <div>
                        <label htmlFor="devis-montant">
                            Montant proposé (FCFA)
                        </label>
                        <input
                            id="devis-montant"
                            type="number"
                            min={1}
                            value={form.data.montant}
                            onChange={(event) =>
                                form.setData('montant', event.target.value)
                            }
                            required
                        />
                        {form.errors.montant && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.montant}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="devis-message">
                            Message au client (optionnel)
                        </label>
                        <textarea
                            id="devis-message"
                            rows={3}
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                        />
                        {form.errors.message && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.message}
                            </p>
                        )}
                    </div>

                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            gap: 12,
                        }}
                    >
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={onClose}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            Envoyer le devis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
