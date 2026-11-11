import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Eye, Plus, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import DocumentContrat from '@/components/espaces/document-contrat';
import { apercuContrat } from '@/lib/contrat-texte';
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

type Modele = {
    id: string;
    nom: string;
    type: string;
    statut: string;
    contenu: string;
};
type ClauseItem = {
    id: string;
    titre: string;
    contenu: string;
    ordre: number;
};
type Contrat = {
    id: string;
    reference: string;
    objet: string;
    partie: string;
    type: string;
    statut: string;
    estSigne: boolean;
    rectificationDemandee: boolean;
    misAJourLe: string | null;
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
    peutTelecharger: boolean;
    variables: Record<string, string>;
};

type Filtre = 'a-traiter' | 'attente' | 'actifs' | 'archives' | 'tous';

const FILTRES: {
    value: Filtre;
    label: string;
    statuts: string[] | null;
}[] = [
    { value: 'a-traiter', label: 'À traiter', statuts: ['BROUILLON', 'SIGNE'] },
    { value: 'attente', label: 'En attente de signature', statuts: ['ENVOYE'] },
    { value: 'actifs', label: 'Actifs', statuts: ['ACTIF'] },
    {
        value: 'archives',
        label: 'Archivés',
        statuts: ['TERMINE', 'RESILIE', 'REFUSE'],
    },
    { value: 'tous', label: 'Tous', statuts: null },
];

/** The button that leads to the next step of a contract, from the list. */
function actionContrat(contrat: Contrat): string {
    if (contrat.statut === 'BROUILLON') {
        return contrat.rectificationDemandee
            ? 'Corriger et renvoyer'
            : 'Relire et envoyer';
    }

    return contrat.statut === 'SIGNE' ? 'Valider' : 'Ouvrir';
}

export default function AdminContrats({
    modeles,
    clauses,
    contrats,
    demandesAContractualiser,
    peutCreer,
    peutModifier,
    peutSupprimer,
    peutTelecharger,
    variables,
}: Props) {
    const [ongletActif, setOngletActif] = useState<
        'contrats' | 'modeles' | 'clauses'
    >('contrats');

    const aTraiter = contrats.filter((contrat) =>
        ['BROUILLON', 'SIGNE'].includes(contrat.statut),
    ).length;
    const [filtre, setFiltre] = useState<Filtre>(
        aTraiter > 0 ? 'a-traiter' : 'tous',
    );
    const [recherche, setRecherche] = useState('');
    const [generationOuverte, setGenerationOuverte] = useState(false);
    const [modeleEnEdition, setModeleEnEdition] = useState<
        Modele | 'nouveau' | null
    >(null);
    const [clauseEnEdition, setClauseEnEdition] = useState<
        ClauseItem | 'nouvelle' | null
    >(null);
    const [apercuOuvert, setApercuOuvert] = useState(false);

    const contratsFiltres = useMemo(() => {
        const statuts = FILTRES.find((item) => item.value === filtre)?.statuts;
        const terme = recherche.trim().toLowerCase();

        return contrats.filter(
            (contrat) =>
                (statuts === null ||
                    statuts === undefined ||
                    statuts.includes(contrat.statut)) &&
                (terme === '' ||
                    [contrat.reference, contrat.objet, contrat.partie].some(
                        (valeur) => valeur.toLowerCase().includes(terme),
                    )),
        );
    }, [contrats, filtre, recherche]);

    const compter = (statuts: string[] | null) =>
        statuts === null
            ? contrats.length
            : contrats.filter((contrat) => statuts.includes(contrat.statut))
                  .length;

    const contratForm = useForm({
        demande_id: demandesAContractualiser[0]?.id ?? '',
        modele_contrat_id: '',
    });

    const submitContrat = (event: React.FormEvent) => {
        event.preventDefault();
        contratForm.post(contratsAdmin.store().url);
    };

    const changerStatutModele = (modele: Modele, statut: string) => {
        router.patch(
            modelesContratAdmin.update(modele.id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    const deplacerClause = (clause: ClauseItem, direction: 'haut' | 'bas') => {
        router.patch(
            clausesAdmin.deplacer(clause.id).url,
            { direction },
            { preserveScroll: true, preserveState: true },
        );
    };

    const supprimerClause = (clause: ClauseItem) => {
        if (
            !confirm(
                `Supprimer l'article « ${clause.titre} » ? Les contrats déjà rédigés gardent leur texte.`,
            )
        ) {
            return;
        }
        router.delete(clausesAdmin.destroy(clause.id).url, {
            preserveScroll: true,
        });
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
                        Suivez les contrats à envoyer et à valider, et rédigez
                        le texte type utilisé pour les nouveaux contrats.
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
                    {aTraiter > 0 && (
                        <span
                            className="chip chip-orange"
                            style={{ marginLeft: 8, fontSize: 11 }}
                        >
                            {aTraiter} à traiter
                        </span>
                    )}
                </button>
                <button
                    type="button"
                    className={`account-tab${ongletActif === 'clauses' ? ' active' : ''}`}
                    onClick={() => setOngletActif('clauses')}
                >
                    Articles du contrat ({clauses.length})
                </button>
                <button
                    type="button"
                    className={`account-tab${ongletActif === 'modeles' ? ' active' : ''}`}
                    onClick={() => setOngletActif('modeles')}
                >
                    Modèles ({modeles.length})
                </button>
            </div>

            {ongletActif === 'contrats' && (
                <>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 12,
                            flexWrap: 'wrap',
                            marginBottom: 16,
                        }}
                    >
                        <div
                            className="account-tabs"
                            style={{ marginBottom: 0, flexWrap: 'wrap' }}
                            role="group"
                            aria-label="Filtrer les contrats"
                        >
                            {FILTRES.map((item) => (
                                <button
                                    key={item.value}
                                    type="button"
                                    aria-pressed={filtre === item.value}
                                    className={`account-tab${filtre === item.value ? ' active' : ''}`}
                                    onClick={() => setFiltre(item.value)}
                                >
                                    {item.label} ({compter(item.statuts)})
                                </button>
                            ))}
                        </div>
                        <div
                            style={{
                                display: 'flex',
                                gap: 10,
                                alignItems: 'center',
                                flexWrap: 'wrap',
                            }}
                        >
                            <label
                                style={{
                                    position: 'relative',
                                    margin: 0,
                                    display: 'block',
                                }}
                            >
                                <span className="sr-only">
                                    Rechercher un contrat
                                </span>
                                <Search
                                    size={15}
                                    style={{
                                        position: 'absolute',
                                        left: 12,
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        color: 'var(--dag-text-muted)',
                                    }}
                                />
                                <input
                                    type="search"
                                    placeholder="Client, référence…"
                                    value={recherche}
                                    onChange={(event) =>
                                        setRecherche(event.target.value)
                                    }
                                    style={{ paddingLeft: 34, minWidth: 220 }}
                                />
                            </label>
                            {peutCreer &&
                                demandesAContractualiser.length > 0 && (
                                    <button
                                        type="button"
                                        className="btn-outline"
                                        onClick={() =>
                                            setGenerationOuverte(
                                                !generationOuverte,
                                            )
                                        }
                                    >
                                        <Plus
                                            size={15}
                                            style={{ marginRight: 6 }}
                                        />
                                        Générer un contrat
                                    </button>
                                )}
                        </div>
                    </div>

                    {generationOuverte && (
                        <form
                            onSubmit={submitContrat}
                            className="card"
                            style={{
                                padding: 24,
                                marginBottom: 20,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            <div>
                                <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                                    Générer un contrat pour une demande
                                </p>
                                <p
                                    className="muted"
                                    style={{ fontSize: 12.5, marginTop: 4 }}
                                >
                                    Normalement, le contrat part automatiquement
                                    avec le devis. Utilisez ce formulaire pour
                                    une demande qui n'en a pas encore.
                                </p>
                            </div>
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
                                        Modèle
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
                                        <option value="">
                                            Modèle par défaut
                                        </option>
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
                        {contratsFiltres.length === 0 ? (
                            <p className="data-table-empty">
                                {filtre === 'a-traiter' && recherche === ''
                                    ? 'Aucun contrat à traiter : tout est à jour.'
                                    : 'Aucun contrat ne correspond.'}
                            </p>
                        ) : (
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Contrat</th>
                                            <th>Client</th>
                                            <th>Statut</th>
                                            <th>Mis à jour</th>
                                            <th />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {contratsFiltres.map((contrat) => (
                                            <tr key={contrat.id}>
                                                <td>
                                                    <Link
                                                        href={contratsAdmin.show(
                                                            contrat.id,
                                                        )}
                                                        style={{
                                                            fontWeight: 600,
                                                            color: 'var(--dag-navy)',
                                                        }}
                                                    >
                                                        {contrat.objet}
                                                    </Link>
                                                    <span
                                                        className="muted"
                                                        style={{
                                                            display: 'block',
                                                            fontSize: 12,
                                                        }}
                                                    >
                                                        {contrat.reference} ·{' '}
                                                        {TYPE_LABEL[
                                                            contrat.type
                                                        ] ?? contrat.type}
                                                    </span>
                                                </td>
                                                <td>{contrat.partie}</td>
                                                <td>
                                                    <span
                                                        style={{
                                                            display: 'flex',
                                                            gap: 6,
                                                            flexWrap: 'wrap',
                                                        }}
                                                    >
                                                        <span
                                                            className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                                                        >
                                                            {STATUT_LABEL[
                                                                contrat.statut
                                                            ] ?? contrat.statut}
                                                        </span>
                                                        {contrat.rectificationDemandee && (
                                                            <span className="chip chip-orange">
                                                                Rectification
                                                                demandée
                                                            </span>
                                                        )}
                                                    </span>
                                                </td>
                                                <td className="muted">
                                                    {contrat.misAJourLe ?? '—'}
                                                </td>
                                                <td
                                                    style={{
                                                        textAlign: 'right',
                                                        whiteSpace: 'nowrap',
                                                    }}
                                                >
                                                    {peutTelecharger &&
                                                        contrat.estSigne && (
                                                            <a
                                                                href={
                                                                    contratsAdmin.pdf(
                                                                        contrat.id,
                                                                    ).url
                                                                }
                                                                className="link-action"
                                                                style={{
                                                                    marginRight: 14,
                                                                }}
                                                            >
                                                                PDF
                                                            </a>
                                                        )}
                                                    <Link
                                                        href={contratsAdmin.show(
                                                            contrat.id,
                                                        )}
                                                        className={
                                                            [
                                                                'BROUILLON',
                                                                'SIGNE',
                                                            ].includes(
                                                                contrat.statut,
                                                            ) && peutModifier
                                                                ? 'btn-primary'
                                                                : 'link-action'
                                                        }
                                                        style={
                                                            [
                                                                'BROUILLON',
                                                                'SIGNE',
                                                            ].includes(
                                                                contrat.statut,
                                                            ) && peutModifier
                                                                ? {
                                                                      padding:
                                                                          '6px 14px',
                                                                      fontSize: 12.5,
                                                                  }
                                                                : undefined
                                                        }
                                                    >
                                                        {peutModifier
                                                            ? actionContrat(
                                                                  contrat,
                                                              )
                                                            : 'Ouvrir'}
                                                    </Link>
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
                    <div
                        className="card"
                        style={{
                            padding: 20,
                            marginBottom: 20,
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 16,
                            flexWrap: 'wrap',
                        }}
                    >
                        <p
                            className="muted"
                            style={{
                                fontSize: 13.5,
                                lineHeight: 1.6,
                                flex: '1 1 320px',
                            }}
                        >
                            Ces articles sont ajoutés, dans cet ordre et
                            numérotés automatiquement, à chaque nouveau contrat.
                            Les contrats déjà rédigés gardent leur texte.
                        </p>
                        <div style={{ display: 'flex', gap: 10 }}>
                            <button
                                type="button"
                                className="btn-outline"
                                onClick={() => setApercuOuvert(true)}
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 6,
                                }}
                            >
                                <Eye size={15} /> Aperçu du contrat
                            </button>
                            {peutCreer && (
                                <button
                                    type="button"
                                    className="btn-primary"
                                    onClick={() =>
                                        setClauseEnEdition('nouvelle')
                                    }
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 6,
                                    }}
                                >
                                    <Plus size={15} /> Ajouter un article
                                </button>
                            )}
                        </div>
                    </div>

                    {clauses.length === 0 ? (
                        <div className="card" style={{ padding: 24 }}>
                            <p className="data-table-empty">
                                Aucun article pour le moment.
                            </p>
                        </div>
                    ) : (
                        <ol
                            style={{
                                listStyle: 'none',
                                margin: 0,
                                padding: 0,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 12,
                            }}
                        >
                            {clauses.map((clause, index) => (
                                <li
                                    key={clause.id}
                                    className="card"
                                    style={{
                                        padding: 18,
                                        display: 'flex',
                                        gap: 16,
                                        alignItems: 'flex-start',
                                        flexWrap: 'wrap',
                                    }}
                                >
                                    <div style={{ flex: '1 1 320px' }}>
                                        <p
                                            className="editeur-contrat-numero"
                                            style={{ marginBottom: 2 }}
                                        >
                                            Article {index + 1}
                                        </p>
                                        <p
                                            style={{
                                                fontWeight: 700,
                                                color: 'var(--dag-navy)',
                                                marginBottom: 4,
                                            }}
                                        >
                                            {clause.titre}
                                        </p>
                                        <p
                                            className="muted"
                                            style={{
                                                fontSize: 13,
                                                lineHeight: 1.6,
                                                display: '-webkit-box',
                                                WebkitLineClamp: 2,
                                                WebkitBoxOrient: 'vertical',
                                                overflow: 'hidden',
                                            }}
                                        >
                                            {clause.contenu}
                                        </p>
                                    </div>
                                    <div
                                        className="editeur-contrat-actions"
                                        style={{ alignItems: 'center' }}
                                    >
                                        {peutModifier && (
                                            <>
                                                <button
                                                    type="button"
                                                    disabled={index === 0}
                                                    onClick={() =>
                                                        deplacerClause(
                                                            clause,
                                                            'haut',
                                                        )
                                                    }
                                                    aria-label={`Monter l'article ${clause.titre}`}
                                                    title="Monter"
                                                >
                                                    <ArrowUp size={15} />
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled={
                                                        index ===
                                                        clauses.length - 1
                                                    }
                                                    onClick={() =>
                                                        deplacerClause(
                                                            clause,
                                                            'bas',
                                                        )
                                                    }
                                                    aria-label={`Descendre l'article ${clause.titre}`}
                                                    title="Descendre"
                                                >
                                                    <ArrowDown size={15} />
                                                </button>
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    style={{
                                                        width: 'auto',
                                                        padding: '0 12px',
                                                    }}
                                                    onClick={() =>
                                                        setClauseEnEdition(
                                                            clause,
                                                        )
                                                    }
                                                >
                                                    Modifier
                                                </button>
                                            </>
                                        )}
                                        {peutSupprimer && (
                                            <button
                                                type="button"
                                                className="danger"
                                                style={{
                                                    width: 'auto',
                                                    padding: '0 12px',
                                                }}
                                                onClick={() =>
                                                    supprimerClause(clause)
                                                }
                                            >
                                                Supprimer
                                            </button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ol>
                    )}
                </>
            )}

            {ongletActif === 'modeles' && (
                <>
                    <div
                        className="card"
                        style={{
                            padding: 20,
                            marginBottom: 20,
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 16,
                            flexWrap: 'wrap',
                        }}
                    >
                        <p
                            className="muted"
                            style={{
                                fontSize: 13.5,
                                lineHeight: 1.6,
                                flex: '1 1 320px',
                            }}
                        >
                            Le modèle est l'introduction du contrat, avant les
                            articles. Pour chaque type de contrat, le modèle
                            marqué « Par défaut » est utilisé pour les nouveaux
                            contrats.
                        </p>
                        {peutCreer && (
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={() => setModeleEnEdition('nouveau')}
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 6,
                                }}
                            >
                                <Plus size={15} /> Nouveau modèle
                            </button>
                        )}
                    </div>

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
                                            <th>Modèle</th>
                                            <th>Type de contrat</th>
                                            <th>Utilisation</th>
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
                                                            ? 'Par défaut'
                                                            : 'Non utilisé'}
                                                    </span>
                                                </td>
                                                <td
                                                    style={{
                                                        textAlign: 'right',
                                                        whiteSpace: 'nowrap',
                                                    }}
                                                >
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        onClick={() =>
                                                            setModeleEnEdition(
                                                                modele,
                                                            )
                                                        }
                                                    >
                                                        {peutModifier
                                                            ? 'Modifier'
                                                            : 'Voir'}
                                                    </button>
                                                    {peutModifier &&
                                                        modele.statut !==
                                                            'ACTIF' && (
                                                            <button
                                                                type="button"
                                                                className="link-action"
                                                                style={{
                                                                    marginLeft: 14,
                                                                }}
                                                                onClick={() =>
                                                                    changerStatutModele(
                                                                        modele,
                                                                        'ACTIF',
                                                                    )
                                                                }
                                                            >
                                                                Utiliser par
                                                                défaut
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

            {clauseEnEdition && (
                <ClauseModal
                    clause={
                        clauseEnEdition === 'nouvelle' ? null : clauseEnEdition
                    }
                    variables={variables}
                    onClose={() => setClauseEnEdition(null)}
                />
            )}

            {modeleEnEdition && (
                <ModeleModal
                    modele={
                        modeleEnEdition === 'nouveau' ? null : modeleEnEdition
                    }
                    clauses={clauses}
                    variables={variables}
                    lectureSeule={
                        !peutModifier && modeleEnEdition !== 'nouveau'
                    }
                    onClose={() => setModeleEnEdition(null)}
                />
            )}

            {apercuOuvert && (
                <ApercuModal
                    modeles={modeles}
                    clauses={clauses}
                    onClose={() => setApercuOuvert(false)}
                />
            )}
        </>
    );
}

function Modal({
    titre,
    sousTitre,
    large = false,
    onClose,
    children,
}: {
    titre: string;
    sousTitre?: string;
    large?: boolean;
    onClose: () => void;
    children: React.ReactNode;
}) {
    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label={titre}
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div
                className="modal-panel"
                style={{ maxWidth: large ? 860 : 620 }}
            >
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    {titre}
                </h2>
                {sousTitre && (
                    <p
                        style={{
                            marginTop: 8,
                            marginBottom: 20,
                            fontSize: 13.5,
                            lineHeight: 1.6,
                            color: 'var(--dag-text-muted)',
                        }}
                    >
                        {sousTitre}
                    </p>
                )}
                {children}
            </div>
        </div>
    );
}

function Erreur({ message }: { message?: string }) {
    return message ? (
        <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
            {message}
        </p>
    ) : null;
}

function ClauseModal({
    clause,
    variables,
    onClose,
}: {
    clause: ClauseItem | null;
    variables: Record<string, string>;
    onClose: () => void;
}) {
    const form = useForm({
        titre: clause?.titre ?? '',
        contenu: clause?.contenu ?? '',
        ordre: clause?.ordre ?? null,
    });
    const [apercu, setApercu] = useState(false);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (clause === null) {
            form.transform(({ titre, contenu }) => ({ titre, contenu }));
            form.post(clausesAdmin.store().url, {
                preserveScroll: true,
                onSuccess: onClose,
            });

            return;
        }

        form.patch(clausesAdmin.update(clause.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Modal
            titre={
                clause === null ? 'Ajouter un article' : "Modifier l'article"
            }
            sousTitre={
                clause === null
                    ? "L'article est ajouté à la fin des nouveaux contrats ; vous pourrez le déplacer ensuite."
                    : "La modification s'applique aux contrats rédigés à partir de maintenant ; les contrats déjà rédigés gardent leur texte."
            }
            onClose={onClose}
        >
            <form
                onSubmit={submit}
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
            >
                <div>
                    <label htmlFor="clause-titre">Titre de l'article</label>
                    <input
                        id="clause-titre"
                        value={form.data.titre}
                        placeholder="Ex. Délai de prestation"
                        maxLength={85}
                        onChange={(event) =>
                            form.setData('titre', event.target.value)
                        }
                        required
                    />
                    <Erreur message={form.errors.titre} />
                </div>
                <div>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                        }}
                    >
                        <label htmlFor="clause-contenu">Texte</label>
                        <button
                            type="button"
                            className="link-action"
                            style={{ fontSize: 12.5 }}
                            onClick={() => setApercu(!apercu)}
                        >
                            {apercu ? 'Modifier' : 'Aperçu'}
                        </button>
                    </div>
                    {apercu ? (
                        <div className="editeur-contrat-apercu">
                            <DocumentContrat
                                contenu={apercuContrat('', [
                                    {
                                        titre: form.data.titre || 'Article',
                                        contenu: form.data.contenu,
                                    },
                                ])}
                            />
                        </div>
                    ) : (
                        <textarea
                            id="clause-contenu"
                            rows={9}
                            value={form.data.contenu}
                            placeholder="Une ligne vide sépare deux paragraphes ; une ligne commençant par « - » devient une puce."
                            onChange={(event) =>
                                form.setData('contenu', event.target.value)
                            }
                            required
                        />
                    )}
                    <Erreur message={form.errors.contenu} />
                    <VariablesDisponibles variables={variables} />
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
                        {clause === null ? "Ajouter l'article" : 'Enregistrer'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ModeleModal({
    modele,
    clauses,
    variables,
    lectureSeule,
    onClose,
}: {
    modele: Modele | null;
    clauses: ClauseItem[];
    variables: Record<string, string>;
    lectureSeule: boolean;
    onClose: () => void;
}) {
    const form = useForm({
        nom: modele?.nom ?? '',
        type: modele?.type ?? 'PRESTATION_SERVICE',
        contenu: modele?.contenu ?? '',
    });
    const [apercu, setApercu] = useState(lectureSeule);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (modele === null) {
            form.post(modelesContratAdmin.store().url, {
                preserveScroll: true,
                onSuccess: onClose,
            });

            return;
        }

        form.transform(({ nom, contenu }) => ({ nom, contenu }));
        form.patch(modelesContratAdmin.update(modele.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Modal
            titre={
                modele === null
                    ? 'Nouveau modèle'
                    : lectureSeule
                      ? modele.nom
                      : 'Modifier le modèle'
            }
            sousTitre="L'introduction du contrat, suivie des articles. Les contrats déjà rédigés gardent leur texte."
            large
            onClose={onClose}
        >
            <form
                onSubmit={submit}
                style={{ display: 'flex', flexDirection: 'column', gap: 16 }}
            >
                {!lectureSeule && (
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(220px, 1fr))',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="modele-nom">Nom</label>
                            <input
                                id="modele-nom"
                                value={form.data.nom}
                                onChange={(event) =>
                                    form.setData('nom', event.target.value)
                                }
                                required
                            />
                            <Erreur message={form.errors.nom} />
                        </div>
                        <div>
                            <label htmlFor="modele-type">Type de contrat</label>
                            <select
                                id="modele-type"
                                value={form.data.type}
                                disabled={modele !== null}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            >
                                {Object.entries(TYPE_LABEL).map(
                                    ([valeur, label]) => (
                                        <option key={valeur} value={valeur}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                        </div>
                    </div>
                )}

                <div>
                    <div
                        className="editeur-contrat-barre"
                        role="tablist"
                        style={{ marginBottom: 10 }}
                    >
                        {!lectureSeule && (
                            <button
                                type="button"
                                role="tab"
                                aria-selected={!apercu}
                                className={!apercu ? 'actif' : ''}
                                onClick={() => setApercu(false)}
                            >
                                Introduction
                            </button>
                        )}
                        <button
                            type="button"
                            role="tab"
                            aria-selected={apercu}
                            className={apercu ? 'actif' : ''}
                            onClick={() => setApercu(true)}
                        >
                            <Eye size={14} /> Contrat complet (exemple)
                        </button>
                    </div>
                    {apercu ? (
                        <div
                            className="editeur-contrat-apercu"
                            style={{ maxHeight: '55vh', overflowY: 'auto' }}
                        >
                            <DocumentContrat
                                contenu={apercuContrat(
                                    form.data.contenu,
                                    clauses,
                                )}
                            />
                        </div>
                    ) : (
                        <>
                            <textarea
                                id="modele-contenu"
                                aria-label="Introduction du contrat"
                                rows={8}
                                value={form.data.contenu}
                                onChange={(event) =>
                                    form.setData('contenu', event.target.value)
                                }
                                required
                            />
                            <Erreur message={form.errors.contenu} />
                            <VariablesDisponibles variables={variables} />
                        </>
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
                        {lectureSeule ? 'Fermer' : 'Annuler'}
                    </button>
                    {!lectureSeule && (
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            {modele === null
                                ? 'Créer le modèle'
                                : 'Enregistrer'}
                        </button>
                    )}
                </div>
            </form>
        </Modal>
    );
}

/**
 * The contract a new demande would receive today, with example values.
 */
function ApercuModal({
    modeles,
    clauses,
    onClose,
}: {
    modeles: Modele[];
    clauses: ClauseItem[];
    onClose: () => void;
}) {
    const actifs = modeles.filter((modele) => modele.statut === 'ACTIF');
    const [modeleId, setModeleId] = useState(actifs[0]?.id ?? '');
    const modele = actifs.find((item) => item.id === modeleId);

    return (
        <Modal
            titre="Aperçu d'un nouveau contrat"
            sousTitre="Le contrat tel qu'un client le recevrait aujourd'hui, avec des valeurs d'exemple à la place des informations de la demande."
            large
            onClose={onClose}
        >
            {actifs.length > 1 && (
                <div style={{ maxWidth: 320, marginBottom: 14 }}>
                    <label htmlFor="apercu-modele">Type de contrat</label>
                    <select
                        id="apercu-modele"
                        value={modeleId}
                        onChange={(event) => setModeleId(event.target.value)}
                    >
                        {actifs.map((item) => (
                            <option key={item.id} value={item.id}>
                                {TYPE_LABEL[item.type] ?? item.type} —{' '}
                                {item.nom}
                            </option>
                        ))}
                    </select>
                </div>
            )}
            <div
                className="editeur-contrat-apercu"
                style={{ maxHeight: '60vh', overflowY: 'auto' }}
            >
                <DocumentContrat
                    contenu={apercuContrat(modele?.contenu ?? '', clauses)}
                />
            </div>
        </Modal>
    );
}

/**
 * Placeholders replaced when a contract is drafted from a modèle or a clause.
 */
function VariablesDisponibles({
    variables,
}: {
    variables: Record<string, string>;
}) {
    return (
        <p
            className="muted"
            style={{ fontSize: 12, marginTop: 6, lineHeight: 1.7 }}
        >
            Variables remplacées automatiquement :{' '}
            {Object.entries(variables).map(([code, libelle], index) => (
                <span key={code}>
                    {index > 0 && ' · '}
                    <code title={libelle}>{code}</code> {libelle.toLowerCase()}
                </span>
            ))}
        </p>
    );
}
