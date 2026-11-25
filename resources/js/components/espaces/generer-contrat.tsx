import { useForm } from '@inertiajs/react';
import {
    Check,
    FileSignature,
    FileText,
    Handshake,
    Repeat,
    Send,
    X,
} from 'lucide-react';
import { useState } from 'react';
import contratsAdmin from '@/routes/espace-admin/contrats';

type TypeGenere =
    | 'PRESTATION_SERVICE'
    | 'ABONNEMENT'
    | 'CONTRAT_PARTENAIRE'
    | 'DEMANDE';

export type DonneesGeneration = {
    structures: { id: string; nom: string; typeLabel: string }[];
    formules: { id: string; nom: string }[];
    domaines: { code: string; libelle: string }[];
    durees: number[];
    peutFacturer: boolean;
    peutPartenariat: boolean;
};

type Modele = { id: string; nom: string; type: string; statut: string };

type Props = {
    generation: DonneesGeneration;
    modeles: Modele[];
    demandes: { id: string; reference: string; objet: string }[];
    onFermer: () => void;
};

const TYPES: {
    valeur: TypeGenere;
    titre: string;
    texte: string;
    icone: typeof FileText;
}[] = [
    {
        valeur: 'PRESTATION_SERVICE',
        titre: 'Prestation de service',
        texte: 'Une prestation convenue avec l’entreprise : elle signe, vous validez, elle paie en ligne.',
        icone: FileText,
    },
    {
        valeur: 'ABONNEMENT',
        titre: 'Abonnement',
        texte: 'Une formule pour une période : même parcours, signature puis paiement.',
        icone: Repeat,
    },
    {
        valeur: 'CONTRAT_PARTENAIRE',
        titre: 'Partenariat',
        texte: 'Le contrat-cadre du juriste : une fois validé, l’entreprise devient Partenaire officiel.',
        icone: Handshake,
    },
    {
        valeur: 'DEMANDE',
        titre: 'Pour une demande existante',
        texte: 'Une demande d’un client qui n’a pas encore son contrat.',
        icone: FileSignature,
    },
];

function libelleDuree(mois: number): string {
    if (mois === 0) {
        return 'Durée indéterminée';
    }

    return mois % 12 === 0
        ? `${mois / 12} an${mois > 12 ? 's' : ''}`
        : `${mois} mois`;
}

function Erreur({ message }: { message?: string }) {
    return message ? <p className="erreur-champ">{message}</p> : null;
}

/**
 * Generates a contract from the « Contrats & clauses » page: its type, the
 * entreprise it is for, what was agreed; then a draft to review, or sent
 * straight away for signature.
 */
export default function GenererContrat({
    generation,
    modeles,
    demandes,
    onFermer,
}: Props) {
    const typesDisponibles = TYPES.filter(
        (type) =>
            (type.valeur !== 'DEMANDE' || demandes.length > 0) &&
            (type.valeur !== 'CONTRAT_PARTENAIRE' ||
                generation.peutPartenariat) &&
            (!['PRESTATION_SERVICE', 'ABONNEMENT'].includes(type.valeur) ||
                generation.peutFacturer),
    );
    const [type, setType] = useState<TypeGenere>(
        typesDisponibles[0]?.valeur ?? 'PRESTATION_SERVICE',
    );

    const form = useForm({
        client_id: '',
        objet: '',
        formule_id: '',
        description: '',
        montant: '',
        modele_contrat_id: '',
        duree_mois: generation.durees[0] ?? 0,
        domaines: [] as string[],
        message: '',
        demande_id: demandes[0]?.id ?? '',
        envoyer: false,
    });

    const modelesDuType = modeles.filter(
        (modele) => modele.statut === 'ACTIF' && modele.type === type,
    );

    const choisirType = (valeur: TypeGenere) => {
        setType(valeur);
        form.clearErrors();
        form.setData('modele_contrat_id', '');
    };

    const basculerDomaine = (code: string) => {
        form.setData(
            'domaines',
            form.data.domaines.includes(code)
                ? form.data.domaines.filter((domaine) => domaine !== code)
                : [...form.data.domaines, code],
        );
    };

    const generer = (envoyer: boolean) => {
        if (type === 'DEMANDE') {
            form.transform((data) => ({
                demande_id: data.demande_id,
                modele_contrat_id: data.modele_contrat_id || null,
            }));
            form.post(contratsAdmin.store().url);

            return;
        }

        if (
            envoyer &&
            !confirm(
                'Envoyer ce contrat à l’entreprise pour signature ? Elle est prévenue tout de suite et le retrouve dans son espace.',
            )
        ) {
            return;
        }

        form.transform((data) =>
            type === 'CONTRAT_PARTENAIRE'
                ? {
                      type,
                      client_id: data.client_id,
                      duree_mois: data.duree_mois,
                      domaines: data.domaines,
                      message: data.message || null,
                  }
                : {
                      type,
                      client_id: data.client_id,
                      objet: type === 'PRESTATION_SERVICE' ? data.objet : null,
                      formule_id:
                          type === 'ABONNEMENT' ? data.formule_id : null,
                      description: data.description,
                      montant: data.montant,
                      modele_contrat_id: data.modele_contrat_id || null,
                      envoyer,
                  },
        );
        form.post(contratsAdmin.generer().url);
    };

    return (
        <div className="card generer-contrat">
            <div className="generer-contrat-tete">
                <div>
                    <p className="generer-contrat-titre">Générer un contrat</p>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Choisissez le type de contrat et l’entreprise : le
                        contrat est rédigé à partir du modèle actif.
                    </p>
                </div>
                <button
                    type="button"
                    className="generer-contrat-fermer"
                    onClick={onFermer}
                    aria-label="Fermer"
                >
                    <X size={18} />
                </button>
            </div>

            <div className="generer-contrat-types" role="radiogroup">
                {typesDisponibles.map((option) => (
                    <button
                        key={option.valeur}
                        type="button"
                        role="radio"
                        aria-checked={type === option.valeur}
                        className={`generer-contrat-type${type === option.valeur ? ' choisi' : ''}`}
                        onClick={() => choisirType(option.valeur)}
                    >
                        <span
                            className="generer-contrat-type-icone"
                            aria-hidden="true"
                        >
                            <option.icone size={18} />
                        </span>
                        <span className="generer-contrat-type-titre">
                            {option.titre}
                        </span>
                        <span className="generer-contrat-type-texte">
                            {option.texte}
                        </span>
                        {type === option.valeur && (
                            <span
                                className="generer-contrat-type-coche"
                                aria-hidden="true"
                            >
                                <Check size={13} />
                            </span>
                        )}
                    </button>
                ))}
            </div>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    generer(type === 'CONTRAT_PARTENAIRE');
                }}
                className="generer-contrat-champs"
            >
                {type === 'DEMANDE' ? (
                    <div>
                        <label htmlFor="generer-demande">Demande</label>
                        <select
                            id="generer-demande"
                            value={form.data.demande_id}
                            onChange={(event) =>
                                form.setData('demande_id', event.target.value)
                            }
                        >
                            {demandes.map((demande) => (
                                <option key={demande.id} value={demande.id}>
                                    {demande.reference} — {demande.objet}
                                </option>
                            ))}
                        </select>
                        <Erreur message={form.errors.demande_id} />
                    </div>
                ) : (
                    <div>
                        <label htmlFor="generer-client">Entreprise</label>
                        <select
                            id="generer-client"
                            value={form.data.client_id}
                            onChange={(event) =>
                                form.setData('client_id', event.target.value)
                            }
                        >
                            <option value="">Choisir une entreprise…</option>
                            {generation.structures.map((structure) => (
                                <option key={structure.id} value={structure.id}>
                                    {structure.nom} ({structure.typeLabel})
                                </option>
                            ))}
                        </select>
                        <Erreur message={form.errors.client_id} />
                    </div>
                )}

                {type === 'PRESTATION_SERVICE' && (
                    <div>
                        <label htmlFor="generer-objet">
                            Objet de la prestation
                        </label>
                        <input
                            id="generer-objet"
                            value={form.data.objet}
                            onChange={(event) =>
                                form.setData('objet', event.target.value)
                            }
                            placeholder="Ex. Réalisation de 120 badges"
                        />
                        <Erreur message={form.errors.objet} />
                    </div>
                )}

                {type === 'ABONNEMENT' && (
                    <div>
                        <label htmlFor="generer-formule">Formule</label>
                        <select
                            id="generer-formule"
                            value={form.data.formule_id}
                            onChange={(event) =>
                                form.setData('formule_id', event.target.value)
                            }
                        >
                            <option value="">Choisir une formule…</option>
                            {generation.formules.map((formule) => (
                                <option key={formule.id} value={formule.id}>
                                    {formule.nom}
                                </option>
                            ))}
                        </select>
                        <Erreur message={form.errors.formule_id} />
                    </div>
                )}

                {(type === 'PRESTATION_SERVICE' || type === 'ABONNEMENT') && (
                    <>
                        <div>
                            <label htmlFor="generer-montant">
                                {type === 'ABONNEMENT'
                                    ? 'Montant pour la période (FCFA)'
                                    : 'Montant convenu (FCFA)'}
                            </label>
                            <input
                                id="generer-montant"
                                type="number"
                                min={1}
                                step={1}
                                value={form.data.montant}
                                onChange={(event) =>
                                    form.setData('montant', event.target.value)
                                }
                                placeholder="Ex. 60000"
                            />
                            <Erreur message={form.errors.montant} />
                        </div>
                        <div className="generer-contrat-large">
                            <label htmlFor="generer-description">
                                Ce qui est convenu
                            </label>
                            <textarea
                                id="generer-description"
                                rows={3}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder="Détail de la prestation : quantités, livrables, délais particuliers… Ce texte figure dans le contrat."
                            />
                            <Erreur message={form.errors.description} />
                        </div>
                        {modelesDuType.length > 1 && (
                            <div>
                                <label htmlFor="generer-modele">Modèle</label>
                                <select
                                    id="generer-modele"
                                    value={form.data.modele_contrat_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'modele_contrat_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">Modèle par défaut</option>
                                    {modelesDuType.map((modele) => (
                                        <option key={modele.id} value={modele.id}>
                                            {modele.nom}
                                        </option>
                                    ))}
                                </select>
                                <Erreur
                                    message={form.errors.modele_contrat_id}
                                />
                            </div>
                        )}
                        <p className="generer-contrat-note generer-contrat-large">
                            La facture du montant est émise avec le contrat :
                            l’entreprise la règle en ligne une fois le contrat
                            signé et validé par vous. Elle retrouve tout dans
                            « Mes demandes ».
                        </p>
                    </>
                )}

                {type === 'CONTRAT_PARTENAIRE' && (
                    <>
                        <div>
                            <label htmlFor="generer-duree">
                                Durée du partenariat
                            </label>
                            <select
                                id="generer-duree"
                                value={form.data.duree_mois}
                                onChange={(event) =>
                                    form.setData(
                                        'duree_mois',
                                        Number(event.target.value),
                                    )
                                }
                            >
                                {generation.durees.map((mois) => (
                                    <option key={mois} value={mois}>
                                        {libelleDuree(mois)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <fieldset className="generer-contrat-large partenariat-fieldset">
                            <legend className="partenariat-libelle">
                                Domaines de collaboration
                            </legend>
                            <div className="partenariat-domaines">
                                {generation.domaines.map((domaine) => {
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
                                                    basculerDomaine(
                                                        domaine.code,
                                                    )
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
                            <Erreur message={form.errors.domaines} />
                        </fieldset>
                        <div className="generer-contrat-large">
                            <label htmlFor="generer-message">
                                Message à l’entreprise (facultatif)
                            </label>
                            <textarea
                                id="generer-message"
                                rows={2}
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                placeholder="Quelques mots pour présenter la proposition de DAG."
                            />
                        </div>
                    </>
                )}

                <div className="generer-contrat-actions generer-contrat-large">
                    {type === 'DEMANDE' ? (
                        <button
                            type="button"
                            className="btn-primary"
                            disabled={form.processing}
                            onClick={() => generer(false)}
                        >
                            <FileSignature size={15} /> Générer le contrat
                        </button>
                    ) : type === 'CONTRAT_PARTENAIRE' ? (
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            <Send size={15} /> Envoyer le contrat à signer
                        </button>
                    ) : (
                        <>
                            <button
                                type="button"
                                className="btn-outline"
                                disabled={form.processing}
                                onClick={() => generer(false)}
                            >
                                <FileSignature size={15} /> Générer et relire
                            </button>
                            <button
                                type="button"
                                className="btn-primary"
                                disabled={form.processing}
                                onClick={() => generer(true)}
                            >
                                <Send size={15} /> Générer et envoyer
                            </button>
                        </>
                    )}
                </div>
            </form>
        </div>
    );
}
