import { Head, Link, router, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
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
    updatedAt: string | null;
    prochaineEtape: {
        titre: string;
        aVous: boolean;
        libelleActeur: string;
    } | null;
};

type FiltreDemandes = 'dag' | 'client' | 'toutes';

type Props = {
    demandes: Demande[];
    peutModifier: boolean;
    peutEnvoyerDevis: boolean;
    peutGenererContrat: boolean;
    peutEnvoyerContrat: boolean;
};

const STATUTS_DEVIS = ['BROUILLON', 'EN_ETUDE', 'DEVIS_ENVOYE'];

export default function AdminDemandes({
    demandes,
    peutEnvoyerDevis,
    peutGenererContrat,
    peutEnvoyerContrat,
}: Props) {
    const [devisPour, setDevisPour] = useState<Demande | null>(null);

    const aTraiter = demandes.filter(
        (demande) => demande.prochaineEtape?.aVous,
    );
    const chezLeClient = demandes.filter(
        (demande) =>
            demande.prochaineEtape !== null && !demande.prochaineEtape.aVous,
    );
    const [filtre, setFiltre] = useState<FiltreDemandes>(
        aTraiter.length > 0 ? 'dag' : 'toutes',
    );
    const demandesAffichees =
        filtre === 'dag'
            ? aTraiter
            : filtre === 'client'
              ? chezLeClient
              : demandes;

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

    return (
        <>
            <Head title="Demandes clients — Administration" />

            <div style={{ marginBottom: 24 }}>
                <h1 style={{ fontSize: 26, color: 'var(--dag-navy)' }}>
                    Demandes clients
                </h1>
                <p
                    className="muted"
                    style={{ marginTop: 6, fontSize: 14, lineHeight: 1.6 }}
                >
                    Devis et contrat envoyés ensemble, signature du client,
                    validation du contrat par DAG, paiement, puis réalisation.
                    La colonne « Prochaine étape » indique qui doit agir.
                </p>
            </div>

            <div
                className="account-tabs"
                style={{ marginBottom: 16 }}
                role="group"
                aria-label="Filtrer les demandes"
            >
                {(
                    [
                        ['dag', `À traiter par DAG (${aTraiter.length})`],
                        [
                            'client',
                            `En attente du client (${chezLeClient.length})`,
                        ],
                        ['toutes', `Toutes (${demandes.length})`],
                    ] as const
                ).map(([valeur, libelle]) => (
                    <button
                        key={valeur}
                        type="button"
                        aria-pressed={filtre === valeur}
                        className={`account-tab${filtre === valeur ? ' active' : ''}`}
                        onClick={() => setFiltre(valeur)}
                    >
                        {libelle}
                    </button>
                ))}
            </div>

            <div className="card" style={{ padding: 24 }}>
                {demandesAffichees.length === 0 ? (
                    <p className="data-table-empty">
                        {filtre === 'dag'
                            ? 'Rien à traiter : toutes les demandes sont à jour.'
                            : 'Aucune demande dans cette catégorie.'}
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Demande</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Prochaine étape</th>
                                    <th>Contrat</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {demandesAffichees.map((demande) => (
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
                                            <span
                                                className="muted"
                                                style={{
                                                    display: 'block',
                                                    fontSize: 12,
                                                }}
                                            >
                                                {demande.reference}
                                                {demande.updatedAt &&
                                                    ` · ${demande.updatedAt}`}
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
                                        <td data-label="Statut">
                                            <span
                                                className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[demande.statut] ??
                                                    demande.statut}
                                            </span>
                                        </td>
                                        <td data-label="Prochaine étape">
                                            <ProchaineEtapeCourte
                                                prochaine={
                                                    demande.prochaineEtape
                                                }
                                            />
                                        </td>
                                        <td data-label="Contrat">
                                            <EtatContrat demande={demande} />
                                        </td>
                                        <td
                                            data-label=""
                                            className="cellule-actions"
                                        >
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 6,
                                                    flexDirection: 'column',
                                                    alignItems: 'flex-start',
                                                }}
                                            >
                                                <Link
                                                    href={demandesAdmin.show(
                                                        demande.id,
                                                    )}
                                                    className="link-action"
                                                >
                                                    Voir
                                                </Link>
                                                {peutEnvoyerDevis &&
                                                    STATUTS_DEVIS.includes(
                                                        demande.statut,
                                                    ) && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            onClick={() =>
                                                                setDevisPour(
                                                                    demande,
                                                                )
                                                            }
                                                        >
                                                            {demande.statut ===
                                                            'DEVIS_ENVOYE'
                                                                ? 'Renvoyer un devis'
                                                                : 'Envoyer un devis'}
                                                        </button>
                                                    )}
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
                                                            Générer le contrat
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
                                                            Envoyer le contrat
                                                        </button>
                                                    )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
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
