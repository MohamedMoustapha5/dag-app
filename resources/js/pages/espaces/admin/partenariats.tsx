import { Head, Link, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Check, FileSignature, Handshake, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import SuiviProcedure, {
    type Parcours,
} from '@/components/espaces/suivi-procedure';
import contratsAdmin from '@/routes/espace-admin/contrats';
import partenariatsAdmin from '@/routes/espace-admin/partenariats';

type Domaine = { code: string; libelle: string };

type Partenariat = {
    id: string;
    reference: string;
    origine: 'DEMANDE' | 'PROPOSITION';
    etape: string;
    parcours: Parcours;
    clientNom: string;
    clientEmail: string;
    clientTypeLabel: string;
    clientSecteur: string | null;
    domaines: string[];
    message: string | null;
    duree: string | null;
    debut: string | null;
    fin: string | null;
    motifRefus: string | null;
    traitePar: string | null;
    creeLe: string | null;
    contrat: { id: string; reference: string } | null;
};

type Props = {
    partenariats: Partenariat[];
    domaines: Domaine[];
    durees: number[];
    clientsProposables: { id: string; nom: string; typeLabel: string }[];
    actions: { gerer: boolean; validerContrat: boolean; voirContrat: boolean };
};

type Filtre = 'A_TRAITER' | 'EN_COURS' | 'ACTIFS' | 'CLOS' | 'TOUS';

const ETAPE: Record<string, { label: string; chip: string }> = {
    EN_ETUDE: { label: 'À étudier', chip: 'chip-orange' },
    A_SIGNER: { label: 'En signature', chip: 'chip-blue' },
    EN_REDACTION: { label: 'Contrat en révision', chip: 'chip-gray' },
    A_VALIDER: { label: 'Signé, à valider', chip: 'chip-orange' },
    ACTIF: { label: 'Partenaire actif', chip: 'chip-green' },
    TERMINE: { label: 'Terminé', chip: 'chip-gray' },
    REFUSE: { label: 'Refusé', chip: 'chip-red' },
    ANNULE: { label: 'Annulé', chip: 'chip-gray' },
};

const FILTRES: { cle: Filtre; label: string; etapes: string[] | null }[] = [
    { cle: 'A_TRAITER', label: 'À traiter', etapes: ['EN_ETUDE', 'A_VALIDER'] },
    {
        cle: 'EN_COURS',
        label: 'En signature',
        etapes: ['A_SIGNER', 'EN_REDACTION'],
    },
    { cle: 'ACTIFS', label: 'Partenaires actifs', etapes: ['ACTIF'] },
    { cle: 'CLOS', label: 'Clos', etapes: ['TERMINE', 'REFUSE', 'ANNULE'] },
    { cle: 'TOUS', label: 'Tous', etapes: null },
];

function libelleDuree(mois: number): string {
    if (mois === 0) {
        return 'Durée indéterminée';
    }

    return mois % 12 === 0
        ? `${mois / 12} an${mois > 12 ? 's' : ''}`
        : `${mois} mois`;
}

function dateCourte(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR');
}

export default function AdminPartenariats({
    partenariats,
    domaines,
    durees,
    clientsProposables,
    actions,
}: Props) {
    const aTraiter = partenariats.filter((partenariat) =>
        ['EN_ETUDE', 'A_VALIDER'].includes(partenariat.etape),
    ).length;
    const [filtre, setFiltre] = useState<Filtre>(
        aTraiter > 0 ? 'A_TRAITER' : 'TOUS',
    );
    const [propositionOuverte, setPropositionOuverte] = useState(false);

    const visibles = useMemo(() => {
        const etapes = FILTRES.find((ligne) => ligne.cle === filtre)?.etapes;

        return etapes === null || etapes === undefined
            ? partenariats
            : partenariats.filter((partenariat) =>
                  etapes.includes(partenariat.etape),
              );
    }, [filtre, partenariats]);

    const compter = (etapes: string[] | null) =>
        etapes === null
            ? partenariats.length
            : partenariats.filter((partenariat) =>
                  etapes.includes(partenariat.etape),
              ).length;

    const libelleDomaine = (code: string) =>
        domaines.find((domaine) => domaine.code === code)?.libelle ?? code;

    return (
        <>
            <Head title="Partenariats — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/handshake-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Partenariats</h1>
                    <p>
                        Étudiez les demandes des entreprises, proposez un
                        partenariat et validez les contrats signés.
                    </p>
                </div>
            </div>

            <div className="partenariat-barre">
                <div className="partenariat-filtres" role="tablist">
                    {FILTRES.map((ligne) => (
                        <button
                            key={ligne.cle}
                            type="button"
                            role="tab"
                            aria-selected={filtre === ligne.cle}
                            className={
                                filtre === ligne.cle
                                    ? 'btn-primary'
                                    : 'btn-outline'
                            }
                            style={{ padding: '7px 14px', fontSize: 13 }}
                            onClick={() => setFiltre(ligne.cle)}
                        >
                            {ligne.label} ({compter(ligne.etapes)})
                        </button>
                    ))}
                </div>
                {actions.gerer && (
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() =>
                            setPropositionOuverte((ouvert) => !ouvert)
                        }
                    >
                        <Handshake size={16} /> Proposer un partenariat
                    </button>
                )}
            </div>

            {propositionOuverte && actions.gerer && (
                <FormulaireProposition
                    domaines={domaines}
                    durees={durees}
                    clients={clientsProposables}
                    onFermer={() => setPropositionOuverte(false)}
                />
            )}

            {visibles.length === 0 ? (
                <div className="card" style={{ padding: 24 }}>
                    <p className="muted" style={{ fontSize: 13 }}>
                        {partenariats.length === 0
                            ? 'Aucun partenariat pour le moment. Les demandes des entreprises arrivent ici ; vous pouvez aussi proposer un partenariat.'
                            : 'Aucun partenariat dans cette catégorie.'}
                    </p>
                </div>
            ) : (
                <div className="partenariat-liste">
                    {visibles.map((partenariat) => (
                        <CartePartenariat
                            key={partenariat.id}
                            partenariat={partenariat}
                            durees={durees}
                            actions={actions}
                            libelleDomaine={libelleDomaine}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

function CartePartenariat({
    partenariat,
    durees,
    actions,
    libelleDomaine,
}: {
    partenariat: Partenariat;
    durees: number[];
    actions: Props['actions'];
    libelleDomaine: (code: string) => string;
}) {
    const [refusOuvert, setRefusOuvert] = useState(false);
    const acceptation = useForm({ duree_mois: durees[0] });
    const refus = useForm({ motif: '' });
    const [validation, setValidation] = useState(false);
    const etape = ETAPE[partenariat.etape] ?? {
        label: partenariat.etape,
        chip: 'chip-gray',
    };

    const accepter = () => {
        acceptation.post(partenariatsAdmin.accepter(partenariat.id).url, {
            preserveScroll: true,
        });
    };

    const refuser = (event: React.FormEvent) => {
        event.preventDefault();
        refus.post(partenariatsAdmin.refuser(partenariat.id).url, {
            preserveScroll: true,
            onSuccess: () => setRefusOuvert(false),
        });
    };

    const validerContrat = () => {
        if (
            partenariat.contrat === null ||
            !confirm(
                `Valider le contrat signé de « ${partenariat.clientNom} » ? La structure devient Partenaire officiel de Digital Agency Group${partenariat.duree ? ` (${partenariat.duree})` : ''}.`,
            )
        ) {
            return;
        }

        router.patch(
            contratsAdmin.update(partenariat.contrat.id).url,
            { statut: 'ACTIF' },
            {
                preserveScroll: true,
                onStart: () => setValidation(true),
                onFinish: () => setValidation(false),
            },
        );
    };

    const action = partenariat.parcours.prochaine?.action;

    return (
        <div className="card partenariat-carte">
            <div className="partenariat-suivi-tete">
                <div>
                    <p className="partenariat-suivi-titre">
                        {partenariat.clientNom}
                        {partenariat.etape === 'ACTIF' && (
                            <BadgeCheck
                                size={17}
                                className="partenariat-icone-officiel"
                                aria-label="Partenaire officiel"
                            />
                        )}
                    </p>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        {partenariat.clientTypeLabel}
                        {partenariat.clientSecteur &&
                            ` · ${partenariat.clientSecteur}`}
                        {` · ${partenariat.clientEmail}`}
                    </p>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        {partenariat.reference} ·{' '}
                        {partenariat.origine === 'PROPOSITION'
                            ? 'Proposé par DAG'
                            : 'Demande de la structure'}
                        {partenariat.creeLe &&
                            ` · ${dateCourte(partenariat.creeLe)}`}
                        {partenariat.duree && ` · ${partenariat.duree}`}
                        {partenariat.traitePar &&
                            ` · traité par ${partenariat.traitePar}`}
                    </p>
                </div>
                <span className={`chip ${etape.chip}`}>{etape.label}</span>
            </div>

            {partenariat.domaines.length > 0 && (
                <ul className="partenariat-domaines-liste">
                    {partenariat.domaines.map((code) => (
                        <li key={code}>
                            <Check size={14} aria-hidden="true" />
                            {libelleDomaine(code)}
                        </li>
                    ))}
                </ul>
            )}

            {partenariat.message && (
                <blockquote className="partenariat-mot-dag">
                    <p className="partenariat-mot-dag-titre">
                        {partenariat.origine === 'PROPOSITION'
                            ? 'Message envoyé à la structure'
                            : 'Présentation de la structure'}
                    </p>
                    {partenariat.message}
                </blockquote>
            )}

            {partenariat.etape === 'ACTIF' && partenariat.debut && (
                <p className="partenariat-periode">
                    {partenariat.fin
                        ? `Partenaire officiel du ${dateCourte(partenariat.debut)} au ${dateCourte(partenariat.fin)}`
                        : `Partenaire officiel depuis le ${dateCourte(partenariat.debut)} · durée indéterminée`}
                </p>
            )}

            <SuiviProcedure
                parcours={partenariat.parcours}
                compact
                messageTermine={
                    partenariat.etape === 'ACTIF'
                        ? 'Partenariat en vigueur.'
                        : 'Partenariat terminé.'
                }
            >
                {action === 'etudier' && actions.gerer && (
                    <div className="partenariat-actions">
                        <label
                            className="sr-only"
                            htmlFor={`duree-${partenariat.id}`}
                        >
                            Durée du partenariat
                        </label>
                        <select
                            id={`duree-${partenariat.id}`}
                            value={acceptation.data.duree_mois}
                            onChange={(event) =>
                                acceptation.setData(
                                    'duree_mois',
                                    Number(event.target.value),
                                )
                            }
                            style={{ width: 'auto' }}
                        >
                            {durees.map((mois) => (
                                <option key={mois} value={mois}>
                                    {libelleDuree(mois)}
                                </option>
                            ))}
                        </select>
                        <button
                            type="button"
                            className="btn-primary"
                            disabled={acceptation.processing}
                            onClick={accepter}
                        >
                            <Check size={15} /> Accepter et envoyer le contrat
                        </button>
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={() => setRefusOuvert((ouvert) => !ouvert)}
                        >
                            <X size={15} /> Refuser
                        </button>
                    </div>
                )}
                {action === 'valider_contrat' && actions.validerContrat && (
                    <button
                        type="button"
                        className="btn-primary"
                        disabled={validation}
                        onClick={validerContrat}
                    >
                        <BadgeCheck size={15} /> Valider le partenariat
                    </button>
                )}
                {partenariat.contrat && actions.voirContrat && (
                    <Link
                        href={contratsAdmin.show(partenariat.contrat.id)}
                        className="btn-outline"
                    >
                        <FileSignature size={15} /> Contrat{' '}
                        {partenariat.contrat.reference}
                    </Link>
                )}
            </SuiviProcedure>

            {partenariat.etape === 'ACTIF' &&
                partenariat.contrat &&
                actions.voirContrat && (
                    <Link
                        href={contratsAdmin.show(partenariat.contrat.id)}
                        className="link-action"
                        style={{ marginTop: 10, display: 'inline-block' }}
                    >
                        Voir le contrat {partenariat.contrat.reference} →
                    </Link>
                )}

            {refusOuvert && (
                <form onSubmit={refuser} className="partenariat-refus">
                    <label
                        htmlFor={`motif-${partenariat.id}`}
                        className="partenariat-libelle"
                    >
                        Motif du refus (transmis à la structure)
                    </label>
                    <textarea
                        id={`motif-${partenariat.id}`}
                        rows={3}
                        value={refus.data.motif}
                        onChange={(event) =>
                            refus.setData('motif', event.target.value)
                        }
                    />
                    {refus.errors.motif && (
                        <p className="partenariat-erreur">
                            {refus.errors.motif}
                        </p>
                    )}
                    <div className="partenariat-actions">
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={refus.processing}
                        >
                            Confirmer le refus
                        </button>
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={() => setRefusOuvert(false)}
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

function FormulaireProposition({
    domaines,
    durees,
    clients,
    onFermer,
}: {
    domaines: Domaine[];
    durees: number[];
    clients: Props['clientsProposables'];
    onFermer: () => void;
}) {
    const form = useForm<{
        client_id: string;
        duree_mois: number;
        domaines: string[];
        message: string;
    }>({
        client_id: '',
        duree_mois: durees[0],
        domaines: [],
        message: '',
    });

    const basculer = (code: string) => {
        form.setData(
            'domaines',
            form.data.domaines.includes(code)
                ? form.data.domaines.filter((domaine) => domaine !== code)
                : [...form.data.domaines, code],
        );
    };

    const envoyer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(partenariatsAdmin.proposer().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onFermer();
            },
        });
    };

    return (
        <form
            onSubmit={envoyer}
            className="card"
            style={{ padding: 24, marginBottom: 24 }}
        >
            <p className="partenariat-suivi-titre">Proposer un partenariat</p>
            <p className="muted" style={{ fontSize: 13, marginBottom: 18 }}>
                Le contrat de partenariat est rédigé à partir du modèle actif et
                envoyé tout de suite à la structure, qui le signe depuis son
                espace. Vous le validez ensuite.
            </p>

            {clients.length === 0 ? (
                <p className="muted" style={{ fontSize: 13 }}>
                    Toutes les entreprises et organisations ont déjà un
                    partenariat en cours.
                </p>
            ) : (
                <>
                    <div className="partenariat-grille">
                        <div>
                            <label
                                htmlFor="proposition-client"
                                className="partenariat-libelle"
                            >
                                Structure
                            </label>
                            <select
                                id="proposition-client"
                                value={form.data.client_id}
                                onChange={(event) =>
                                    form.setData(
                                        'client_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Choisir une structure…</option>
                                {clients.map((client) => (
                                    <option key={client.id} value={client.id}>
                                        {client.nom} ({client.typeLabel})
                                    </option>
                                ))}
                            </select>
                            {form.errors.client_id && (
                                <p className="partenariat-erreur">
                                    {form.errors.client_id}
                                </p>
                            )}
                        </div>
                        <div>
                            <label
                                htmlFor="proposition-duree"
                                className="partenariat-libelle"
                            >
                                Durée du partenariat
                            </label>
                            <select
                                id="proposition-duree"
                                value={form.data.duree_mois}
                                onChange={(event) =>
                                    form.setData(
                                        'duree_mois',
                                        Number(event.target.value),
                                    )
                                }
                            >
                                {durees.map((mois) => (
                                    <option key={mois} value={mois}>
                                        {libelleDuree(mois)}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <fieldset className="partenariat-fieldset">
                        <legend className="partenariat-libelle">
                            Domaines de collaboration
                        </legend>
                        <div className="partenariat-domaines">
                            {domaines.map((domaine) => {
                                const choisi = form.data.domaines.includes(
                                    domaine.code,
                                );

                                return (
                                    <label
                                        key={domaine.code}
                                        className={`partenariat-domaine${choisi ? ' choisi' : ''}`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={choisi}
                                            onChange={() =>
                                                basculer(domaine.code)
                                            }
                                        />
                                        <span
                                            className="partenariat-domaine-case"
                                            aria-hidden="true"
                                        >
                                            {choisi && <Check size={13} />}
                                        </span>
                                        {domaine.libelle}
                                    </label>
                                );
                            })}
                        </div>
                        {form.errors.domaines && (
                            <p className="partenariat-erreur">
                                {form.errors.domaines}
                            </p>
                        )}
                    </fieldset>

                    <label
                        htmlFor="proposition-message"
                        className="partenariat-libelle"
                    >
                        Message à la structure (facultatif)
                    </label>
                    <textarea
                        id="proposition-message"
                        rows={3}
                        value={form.data.message}
                        onChange={(event) =>
                            form.setData('message', event.target.value)
                        }
                        placeholder="Quelques mots pour présenter la proposition de DAG."
                    />

                    <div
                        className="partenariat-actions"
                        style={{ marginTop: 16 }}
                    >
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            <FileSignature size={15} />
                            {form.processing
                                ? 'Envoi…'
                                : 'Envoyer le contrat à signer'}
                        </button>
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={onFermer}
                        >
                            Annuler
                        </button>
                    </div>
                </>
            )}
        </form>
    );
}
