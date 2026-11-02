import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import clausesAdmin from '@/routes/espace-admin/clauses';
import contratsAdmin from '@/routes/espace-admin/contrats';
import modelesContratAdmin from '@/routes/espace-admin/modeles-contrat';

const TYPE_LABEL: Record<string, string> = {
    PRESTATION_SERVICE: 'Prestation de service',
    ABONNEMENT: 'Abonnement',
    CONVENTION_STAGE: 'Convention de stage',
    CONTRAT_PARTENAIRE: 'Contrat partenaire',
};

type Modele = { id: string; nom: string; type: string; statut: string };
type ClauseItem = { id: string; titre: string; ordre: number };
type Contrat = {
    id: string;
    reference: string;
    objet: string;
    type: string;
    statut: string;
};
type DemandeAContractualiser = { id: string; reference: string; objet: string };

type Props = {
    modeles: Modele[];
    clauses: ClauseItem[];
    contrats: Contrat[];
    demandesAContractualiser: DemandeAContractualiser[];
    peutCreer: boolean;
    peutModifier: boolean;
    peutSupprimer: boolean;
};

export default function AdminContrats({
    modeles,
    clauses,
    contrats,
    demandesAContractualiser,
    peutCreer,
    peutModifier,
    peutSupprimer,
}: Props) {
    const [ongletActif, setOngletActif] = useState<
        'contrats' | 'modeles' | 'clauses'
    >('contrats');

    const contratForm = useForm({
        demande_id: demandesAContractualiser[0]?.id ?? '',
        modele_contrat_id: modeles[0]?.id ?? '',
    });

    const modeleForm = useForm({
        nom: '',
        type: 'PRESTATION_SERVICE',
        contenu: '',
    });

    const clauseForm = useForm({
        titre: '',
        contenu: '',
        ordre: 0,
    });

    const submitContrat = (event: React.FormEvent) => {
        event.preventDefault();
        contratForm.post(contratsAdmin.store().url);
    };

    const submitModele = (event: React.FormEvent) => {
        event.preventDefault();
        modeleForm.post(modelesContratAdmin.store().url, {
            onSuccess: () => modeleForm.reset(),
        });
    };

    const activerModele = (id: string, statutActuel: string) => {
        router.patch(
            modelesContratAdmin.update(id).url,
            { statut: statutActuel === 'ACTIF' ? 'BROUILLON' : 'ACTIF' },
            { preserveScroll: true },
        );
    };

    const submitClause = (event: React.FormEvent) => {
        event.preventDefault();
        clauseForm.post(clausesAdmin.store().url, {
            onSuccess: () => clauseForm.reset(),
        });
    };

    const supprimerClause = (id: string) => {
        if (!confirm('Supprimer cette clause ?')) {
            return;
        }
        router.delete(clausesAdmin.destroy(id).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Contrats & clauses — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/branding-design.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Contrats & clauses</h1>
                    <p>
                        Modèles de contrat, bibliothèque de clauses et suivi des
                        contrats générés.
                    </p>
                </div>
            </div>

            <div className="account-tabs" style={{ marginBottom: 24 }}>
                <button
                    type="button"
                    className={`account-tab${ongletActif === 'contrats' ? ' active' : ''}`}
                    onClick={() => setOngletActif('contrats')}
                >
                    Contrats ({contrats.length})
                </button>
                <button
                    type="button"
                    className={`account-tab${ongletActif === 'modeles' ? ' active' : ''}`}
                    onClick={() => setOngletActif('modeles')}
                >
                    Modèles ({modeles.length})
                </button>
                <button
                    type="button"
                    className={`account-tab${ongletActif === 'clauses' ? ' active' : ''}`}
                    onClick={() => setOngletActif('clauses')}
                >
                    Clauses ({clauses.length})
                </button>
            </div>

            {ongletActif === 'contrats' && (
                <>
                    {peutCreer && demandesAContractualiser.length > 0 && (
                        <form
                            onSubmit={submitContrat}
                            className="card"
                            style={{
                                padding: 24,
                                marginBottom: 28,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                                Générer un contrat pour une demande
                            </p>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit, minmax(220px, 1fr))',
                                    gap: 14,
                                }}
                            >
                                <div>
                                    <label htmlFor="demande_id">Demande</label>
                                    <select
                                        id="demande_id"
                                        value={contratForm.data.demande_id}
                                        onChange={(event) =>
                                            contratForm.setData(
                                                'demande_id',
                                                event.target.value,
                                            )
                                        }
                                    >
                                        {demandesAContractualiser.map(
                                            (demande) => (
                                                <option
                                                    key={demande.id}
                                                    value={demande.id}
                                                >
                                                    {demande.reference} —{' '}
                                                    {demande.objet}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="modele_contrat_id">
                                        Modèle (optionnel)
                                    </label>
                                    <select
                                        id="modele_contrat_id"
                                        value={
                                            contratForm.data.modele_contrat_id
                                        }
                                        onChange={(event) =>
                                            contratForm.setData(
                                                'modele_contrat_id',
                                                event.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Aucun</option>
                                        {modeles
                                            .filter(
                                                (modele) =>
                                                    modele.statut === 'ACTIF',
                                            )
                                            .map((modele) => (
                                                <option
                                                    key={modele.id}
                                                    value={modele.id}
                                                >
                                                    {modele.nom}
                                                </option>
                                            ))}
                                    </select>
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={contratForm.processing}
                                className="btn-primary"
                                style={{ alignSelf: 'flex-start' }}
                            >
                                {contratForm.processing
                                    ? 'Création…'
                                    : 'Générer le contrat'}
                            </button>
                        </form>
                    )}

                    <div className="card" style={{ padding: 24 }}>
                        {contrats.length === 0 ? (
                            <p className="data-table-empty">
                                Aucun contrat pour le moment.
                            </p>
                        ) : (
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Objet</th>
                                            <th>Type</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {contrats.map((contrat) => (
                                            <tr key={contrat.id}>
                                                <td>
                                                    <Link
                                                        href={contratsAdmin.show(
                                                            contrat.id,
                                                        )}
                                                        className="link-action"
                                                    >
                                                        {contrat.reference}
                                                    </Link>
                                                </td>
                                                <td
                                                    style={{
                                                        fontWeight: 600,
                                                    }}
                                                >
                                                    {contrat.objet}
                                                </td>
                                                <td className="muted">
                                                    {TYPE_LABEL[contrat.type] ??
                                                        contrat.type}
                                                </td>
                                                <td>
                                                    <span
                                                        className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                                                    >
                                                        {STATUT_LABEL[
                                                            contrat.statut
                                                        ] ?? contrat.statut}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </>
            )}

            {ongletActif === 'modeles' && (
                <>
                    {peutCreer && (
                        <form
                            onSubmit={submitModele}
                            className="card"
                            style={{
                                padding: 24,
                                marginBottom: 28,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                                Créer un modèle de contrat
                            </p>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit, minmax(220px, 1fr))',
                                    gap: 14,
                                }}
                            >
                                <div>
                                    <label htmlFor="nom">Nom</label>
                                    <input
                                        id="nom"
                                        value={modeleForm.data.nom}
                                        onChange={(event) =>
                                            modeleForm.setData(
                                                'nom',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label htmlFor="type">Type</label>
                                    <select
                                        id="type"
                                        value={modeleForm.data.type}
                                        onChange={(event) =>
                                            modeleForm.setData(
                                                'type',
                                                event.target.value,
                                            )
                                        }
                                    >
                                        {Object.entries(TYPE_LABEL).map(
                                            ([valeur, label]) => (
                                                <option
                                                    key={valeur}
                                                    value={valeur}
                                                >
                                                    {label}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </div>
                                <div style={{ gridColumn: '1 / -1' }}>
                                    <label htmlFor="contenu">Contenu</label>
                                    <textarea
                                        id="contenu"
                                        rows={4}
                                        value={modeleForm.data.contenu}
                                        onChange={(event) =>
                                            modeleForm.setData(
                                                'contenu',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={modeleForm.processing}
                                className="btn-primary"
                                style={{ alignSelf: 'flex-start' }}
                            >
                                {modeleForm.processing
                                    ? 'Création…'
                                    : 'Créer le modèle'}
                            </button>
                        </form>
                    )}

                    <div className="card" style={{ padding: 24 }}>
                        {modeles.length === 0 ? (
                            <p className="data-table-empty">
                                Aucun modèle pour le moment.
                            </p>
                        ) : (
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Statut</th>
                                            <th />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {modeles.map((modele) => (
                                            <tr key={modele.id}>
                                                <td
                                                    style={{
                                                        fontWeight: 600,
                                                    }}
                                                >
                                                    {modele.nom}
                                                </td>
                                                <td className="muted">
                                                    {TYPE_LABEL[modele.type] ??
                                                        modele.type}
                                                </td>
                                                <td>
                                                    <span
                                                        className={`chip ${modele.statut === 'ACTIF' ? 'chip-green' : 'chip-gray'}`}
                                                    >
                                                        {modele.statut ===
                                                        'ACTIF'
                                                            ? 'Actif'
                                                            : 'Brouillon'}
                                                    </span>
                                                </td>
                                                <td
                                                    style={{
                                                        textAlign: 'right',
                                                    }}
                                                >
                                                    {peutModifier && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            onClick={() =>
                                                                activerModele(
                                                                    modele.id,
                                                                    modele.statut,
                                                                )
                                                            }
                                                        >
                                                            {modele.statut ===
                                                            'ACTIF'
                                                                ? 'Repasser en brouillon'
                                                                : 'Activer'}
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
            )}

            {ongletActif === 'clauses' && (
                <>
                    {peutCreer && (
                        <form
                            onSubmit={submitClause}
                            className="card"
                            style={{
                                padding: 24,
                                marginBottom: 28,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                                Ajouter une clause
                            </p>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit, minmax(220px, 1fr))',
                                    gap: 14,
                                }}
                            >
                                <div>
                                    <label htmlFor="titre">Titre</label>
                                    <input
                                        id="titre"
                                        value={clauseForm.data.titre}
                                        onChange={(event) =>
                                            clauseForm.setData(
                                                'titre',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label htmlFor="ordre">Ordre</label>
                                    <input
                                        id="ordre"
                                        type="number"
                                        min={0}
                                        value={clauseForm.data.ordre}
                                        onChange={(event) =>
                                            clauseForm.setData(
                                                'ordre',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                </div>
                                <div style={{ gridColumn: '1 / -1' }}>
                                    <label htmlFor="clause-contenu">
                                        Contenu
                                    </label>
                                    <textarea
                                        id="clause-contenu"
                                        rows={3}
                                        value={clauseForm.data.contenu}
                                        onChange={(event) =>
                                            clauseForm.setData(
                                                'contenu',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={clauseForm.processing}
                                className="btn-primary"
                                style={{ alignSelf: 'flex-start' }}
                            >
                                {clauseForm.processing
                                    ? 'Ajout…'
                                    : 'Ajouter la clause'}
                            </button>
                        </form>
                    )}

                    <div className="card" style={{ padding: 24 }}>
                        {clauses.length === 0 ? (
                            <p className="data-table-empty">
                                Aucune clause pour le moment.
                            </p>
                        ) : (
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Titre</th>
                                            <th>Ordre</th>
                                            <th />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {clauses.map((clause) => (
                                            <tr key={clause.id}>
                                                <td
                                                    style={{
                                                        fontWeight: 600,
                                                    }}
                                                >
                                                    {clause.titre}
                                                </td>
                                                <td className="muted">
                                                    {clause.ordre}
                                                </td>
                                                <td
                                                    style={{
                                                        textAlign: 'right',
                                                    }}
                                                >
                                                    {peutSupprimer && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            style={{
                                                                color: 'var(--dag-red)',
                                                            }}
                                                            onClick={() =>
                                                                supprimerClause(
                                                                    clause.id,
                                                                )
                                                            }
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
            )}
        </>
    );
}
