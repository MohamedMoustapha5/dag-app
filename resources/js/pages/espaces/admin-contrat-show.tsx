import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Download, Send } from 'lucide-react';
import { useState } from 'react';
import DocumentContrat from '@/components/espaces/document-contrat';
import EditeurContrat from '@/components/espaces/editeur-contrat';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import contratsAdmin from '@/routes/espace-admin/contrats';
import demandesAdmin from '@/routes/espace-admin/demandes';

type Props = {
    peutModifier: boolean;
    contrat: {
        id: string;
        reference: string;
        objet: string;
        partie: string;
        demandeId: string | null;
        type: string;
        statut: string;
        statutsAccessibles: string[];
        montant: string | null;
        contenu: string | null;
        codeVerification: string;
        signataireNom: string | null;
        dateSignature: string | null;
        signatureContestee: boolean;
        estSigne: boolean;
        identite: {
            telephone: string | null;
            cniNumero: string | null;
            cniLieuDelivrance: string | null;
            cniDateDelivrance: string | null;
            villeResidence: string | null;
            quartierPrestation: string | null;
        } | null;
        rectification: {
            nombre: number;
            max: number;
            motif: string | null;
            demandeeLe: string | null;
        };
    };
    peutTelecharger: boolean;
};

/** The usual life of a contract, shown as steps. */
const ETAPES = [
    { statut: 'BROUILLON', libelle: 'Rédaction' },
    { statut: 'ENVOYE', libelle: 'Envoyé au client' },
    { statut: 'SIGNE', libelle: 'Signé' },
    { statut: 'ACTIF', libelle: 'Validé' },
    { statut: 'TERMINE', libelle: 'Terminé' },
];

type Transition = {
    libelle: string;
    style: 'primaire' | 'secondaire' | 'danger';
    confirmation?: string;
};

/** What each allowed status change means for the team, as a button. */
const TRANSITIONS: Record<string, Transition> = {
    ENVOYE: { libelle: 'Envoyer au client', style: 'primaire' },
    ACTIF: { libelle: 'Valider le contrat', style: 'primaire' },
    TERMINE: {
        libelle: 'Marquer comme terminé',
        style: 'secondaire',
        confirmation:
            'Marquer ce contrat comme terminé ? La prestation est considérée comme achevée.',
    },
    BROUILLON: {
        libelle: 'Reprendre pour corriger',
        style: 'secondaire',
        confirmation:
            "Reprendre ce contrat en brouillon ? Le client ne pourra plus le signer tant qu'il n'est pas renvoyé.",
    },
    REFUSE: {
        libelle: 'Marquer comme refusé',
        style: 'danger',
        confirmation: 'Le client a refusé ce contrat ?',
    },
    RESILIE: {
        libelle: 'Résilier',
        style: 'danger',
        confirmation: 'Résilier ce contrat ? Cette action est définitive.',
    },
};

/** What the team has to do next, by status. */
const PROCHAINE_ACTION: Record<string, { titre: string; texte: string }> = {
    BROUILLON: {
        titre: 'Relisez le contrat puis envoyez-le au client',
        texte: 'Corrigez le texte si besoin ci-dessous : il est enregistré et envoyé en un clic. Le client est prévenu et peut le signer depuis son espace.',
    },
    ENVOYE: {
        titre: 'En attente de la signature du client',
        texte: 'Le client a reçu le contrat. Il peut le signer ou demander une rectification. Pour corriger le texte vous-même, reprenez-le en brouillon.',
    },
    SIGNE: {
        titre: 'Le client a signé : validez le contrat',
        texte: 'Vérifiez la signature et les informations du client, puis validez le contrat : le client est prévenu et peut alors régler sa facture.',
    },
    ACTIF: {
        titre: 'Contrat validé et actif',
        texte: 'Marquez-le comme terminé une fois la prestation achevée.',
    },
    TERMINE: {
        titre: 'Contrat terminé',
        texte: 'La prestation est achevée. Le PDF signé reste disponible.',
    },
    REFUSE: {
        titre: 'Contrat refusé par le client',
        texte: 'Reprenez-le en brouillon pour le corriger et le renvoyer.',
    },
    RESILIE: {
        titre: 'Contrat résilié',
        texte: "Ce contrat n'est plus en vigueur.",
    },
};

export default function AdminContratShow({
    peutModifier,
    peutTelecharger,
    contrat,
}: Props) {
    const modifiable = peutModifier && contrat.statut === 'BROUILLON';
    const contenuForm = useForm({
        contenu: contrat.contenu ?? '',
        envoyer: false,
    });
    const [enCours, setEnCours] = useState<string | null>(null);

    const enregistrer = (envoyer: boolean) => {
        if (
            envoyer &&
            !confirm(
                `Envoyer le contrat ${contrat.reference} à ${contrat.partie} pour signature ?`,
            )
        ) {
            return;
        }

        contenuForm.transform((data) => ({ ...data, envoyer }));
        contenuForm.patch(contratsAdmin.contenu(contrat.id).url, {
            preserveScroll: true,
            onSuccess: () => contenuForm.setDefaults(),
        });
    };

    const changerStatut = (statut: string) => {
        const transition = TRANSITIONS[statut];

        if (transition?.confirmation && !confirm(transition.confirmation)) {
            return;
        }

        setEnCours(statut);
        router.patch(
            contratsAdmin.update(contrat.id).url,
            { statut },
            {
                preserveScroll: true,
                onFinish: () => setEnCours(null),
            },
        );
    };

    const indexCourant = ETAPES.findIndex(
        (etape) => etape.statut === contrat.statut,
    );
    const prochaine = PROCHAINE_ACTION[contrat.statut];
    const transitions = peutModifier
        ? contrat.statutsAccessibles.filter(
              (statut) => TRANSITIONS[statut] !== undefined,
          )
        : [];

    return (
        <>
            <Head title={`Contrat ${contrat.reference} — Administration`} />

            <Link
                href={contratsAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Tous les contrats
            </Link>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 18,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                        {contrat.objet}
                    </h1>
                    <p className="muted" style={{ fontSize: 13, marginTop: 4 }}>
                        {contrat.partie} · {contrat.reference} · Code{' '}
                        {contrat.codeVerification}
                        {contrat.montant &&
                            ` · ${Number(contrat.montant).toLocaleString('fr-FR')} FCFA`}
                        {contrat.demandeId && (
                            <>
                                {' · '}
                                <Link
                                    href={demandesAdmin.show(contrat.demandeId)}
                                    className="link-action"
                                >
                                    Voir la demande
                                </Link>
                            </>
                        )}
                    </p>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[contrat.statut] ?? contrat.statut}
                </span>
            </div>

            {indexCourant !== -1 && (
                <ol
                    className="contrat-etapes"
                    aria-label="Avancement du contrat"
                >
                    {ETAPES.map((etape, index) => (
                        <li
                            key={etape.statut}
                            className={
                                index < indexCourant
                                    ? 'faite'
                                    : index === indexCourant
                                      ? 'courante'
                                      : ''
                            }
                            aria-current={
                                index === indexCourant ? 'step' : undefined
                            }
                        >
                            <span>
                                {index < indexCourant ? (
                                    <Check size={12} />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            {etape.libelle}
                        </li>
                    ))}
                </ol>
            )}

            {contrat.signatureContestee && (
                <div
                    className="card"
                    style={{
                        padding: 20,
                        marginBottom: 24,
                        borderColor: 'var(--dag-red)',
                    }}
                >
                    <p style={{ color: 'var(--dag-red)', fontWeight: 600 }}>
                        La signature de ce contrat a été contestée.
                    </p>
                </div>
            )}

            {contrat.rectification.motif && contrat.statut === 'BROUILLON' && (
                <div
                    className="card"
                    style={{
                        padding: 20,
                        marginBottom: 24,
                        background: 'var(--dag-orange-bg)',
                        borderColor: 'transparent',
                    }}
                >
                    <p style={{ fontWeight: 700, marginBottom: 6 }}>
                        Rectification demandée par le client (
                        {contrat.rectification.nombre}/
                        {contrat.rectification.max})
                        {contrat.rectification.demandeeLe
                            ? ` — le ${contrat.rectification.demandeeLe}`
                            : ''}
                    </p>
                    <p style={{ fontSize: 13.5, whiteSpace: 'pre-line' }}>
                        {contrat.rectification.motif}
                    </p>
                    {contrat.rectification.nombre >=
                        contrat.rectification.max && (
                        <p
                            className="muted"
                            style={{ fontSize: 12.5, marginTop: 8 }}
                        >
                            Le client a utilisé toutes ses demandes de
                            rectification.
                        </p>
                    )}
                </div>
            )}

            {prochaine && (
                <div className="card contrat-action">
                    <div style={{ flex: '1 1 320px' }}>
                        <h2>{prochaine.titre}</h2>
                        <p>{prochaine.texte}</p>
                    </div>
                    <div className="contrat-action-boutons">
                        {modifiable && (
                            <button
                                type="button"
                                className="btn-primary"
                                disabled={contenuForm.processing}
                                onClick={() => enregistrer(true)}
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 8,
                                }}
                            >
                                <Send size={15} />
                                {contenuForm.processing
                                    ? 'Envoi…'
                                    : contenuForm.isDirty
                                      ? 'Enregistrer et envoyer au client'
                                      : 'Envoyer au client'}
                            </button>
                        )}
                        {transitions
                            .filter(
                                (statut) =>
                                    !(modifiable && statut === 'ENVOYE'),
                            )
                            .map((statut) => {
                                const transition = TRANSITIONS[statut];

                                return (
                                    <button
                                        key={statut}
                                        type="button"
                                        disabled={enCours !== null}
                                        className={
                                            transition.style === 'primaire'
                                                ? 'btn-primary'
                                                : transition.style === 'danger'
                                                  ? 'btn-outline btn-danger-outline'
                                                  : 'btn-outline'
                                        }
                                        onClick={() => changerStatut(statut)}
                                    >
                                        {enCours === statut
                                            ? 'Mise à jour…'
                                            : transition.libelle}
                                    </button>
                                );
                            })}
                        {peutTelecharger && contrat.estSigne && (
                            <a
                                href={contratsAdmin.pdf(contrat.id).url}
                                className="btn-outline"
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 8,
                                }}
                            >
                                <Download size={15} /> PDF signé
                            </a>
                        )}
                    </div>
                </div>
            )}

            {(contrat.dateSignature || contrat.identite) && (
                <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                    <h2 style={{ fontSize: 15, marginBottom: 12 }}>
                        Signature du client
                    </h2>
                    <dl
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(200px, 1fr))',
                            gap: 12,
                            fontSize: 13.5,
                        }}
                    >
                        {[
                            ['Signé par', contrat.signataireNom],
                            ['Le', contrat.dateSignature],
                            ['Téléphone', contrat.identite?.telephone],
                            [
                                'CNI',
                                contrat.identite?.cniNumero
                                    ? `N° ${contrat.identite.cniNumero} — ${contrat.identite.cniLieuDelivrance ?? '—'}, ${contrat.identite.cniDateDelivrance ?? '—'}`
                                    : null,
                            ],
                            [
                                'Ville de résidence',
                                contrat.identite?.villeResidence,
                            ],
                            [
                                'Quartier de prestation',
                                contrat.identite?.quartierPrestation,
                            ],
                        ]
                            .filter(([, valeur]) => valeur)
                            .map(([libelle, valeur]) => (
                                <div key={libelle}>
                                    <dt
                                        className="muted"
                                        style={{ fontSize: 12 }}
                                    >
                                        {libelle}
                                    </dt>
                                    <dd style={{ margin: 0, fontWeight: 600 }}>
                                        {valeur}
                                    </dd>
                                </div>
                            ))}
                    </dl>
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 12,
                        flexWrap: 'wrap',
                        marginBottom: 14,
                    }}
                >
                    <h2 style={{ fontSize: 15 }}>Texte du contrat</h2>
                    {modifiable && (
                        <button
                            type="button"
                            className="btn-outline"
                            disabled={
                                contenuForm.processing || !contenuForm.isDirty
                            }
                            onClick={() => enregistrer(false)}
                        >
                            {contenuForm.isDirty
                                ? 'Enregistrer les modifications'
                                : 'Modifications enregistrées'}
                        </button>
                    )}
                </div>

                {modifiable ? (
                    <EditeurContrat
                        key={contrat.id}
                        valeurInitiale={contrat.contenu}
                        onChange={(contenu) =>
                            contenuForm.setData('contenu', contenu)
                        }
                        erreur={contenuForm.errors.contenu}
                    />
                ) : (
                    <>
                        {peutModifier && contrat.statut === 'ENVOYE' && (
                            <p
                                className="muted"
                                style={{ fontSize: 12.5, marginBottom: 12 }}
                            >
                                Pour modifier le texte, reprenez d'abord le
                                contrat pour correction.
                            </p>
                        )}
                        <DocumentContrat contenu={contrat.contenu} />
                    </>
                )}
            </div>
        </>
    );
}
