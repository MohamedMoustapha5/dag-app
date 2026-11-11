import { Head, Link, useForm } from '@inertiajs/react';
import { Download, FileSignature, ImagePlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import DocumentContrat from '@/components/espaces/document-contrat';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import { contrats as contratsIndex } from '@/routes/espace-client';
import contrats from '@/routes/espace-client/contrats';

type Props = {
    contrat: {
        id: string;
        reference: string;
        objet: string;
        statut: string;
        montant: string | null;
        contenu: string | null;
        codeVerification: string;
        signataireNom: string | null;
        dateSignature: string | null;
        estSigne: boolean;
        peutSigner: boolean;
        nomSuggere: string;
    };
    identite: {
        exigee: boolean;
        telephone: string | null;
        cniNumero: string | null;
        cniLieuDelivrance: string | null;
        cniDateDelivrance: string | null;
        villeResidence: string | null;
        quartierPrestation: string | null;
    };
    rectification: {
        restantes: number;
        max: number;
        peutDemander: boolean;
    };
};

type ChampIdentite =
    | 'telephone'
    | 'cni_numero'
    | 'cni_lieu_delivrance'
    | 'cni_date_delivrance'
    | 'ville_residence'
    | 'quartier_prestation';

const CHAMPS_IDENTITE: {
    nom: ChampIdentite;
    libelle: string;
    type?: string;
    placeholder?: string;
    autoComplete?: string;
}[] = [
    {
        nom: 'telephone',
        libelle: 'Numéro de téléphone',
        type: 'tel',
        placeholder: 'Ex. 6 90 00 00 00',
        autoComplete: 'tel',
    },
    { nom: 'cni_numero', libelle: 'N° de la CNI' },
    {
        nom: 'cni_lieu_delivrance',
        libelle: 'CNI faite à',
        placeholder: 'Ex. Douala',
    },
    {
        nom: 'cni_date_delivrance',
        libelle: 'Date de délivrance de la CNI',
        type: 'date',
    },
    {
        nom: 'ville_residence',
        libelle: 'Ville de résidence',
        autoComplete: 'address-level2',
    },
    {
        nom: 'quartier_prestation',
        libelle: 'Quartier de la prestation',
        placeholder: 'Ex. Bonapriso',
    },
];

export default function ContratsShow({
    contrat,
    identite,
    rectification,
}: Props) {
    const form = useForm<
        {
            signature: File | null;
            nom: string;
            accepte: boolean;
        } & Record<ChampIdentite, string>
    >({
        signature: null,
        nom: contrat.nomSuggere,
        accepte: false,
        telephone: identite.telephone ?? '',
        cni_numero: identite.cniNumero ?? '',
        cni_lieu_delivrance: identite.cniLieuDelivrance ?? '',
        cni_date_delivrance: identite.cniDateDelivrance ?? '',
        ville_residence: identite.villeResidence ?? '',
        quartier_prestation: identite.quartierPrestation ?? '',
    });
    const rectificationForm = useForm({ motif: '' });
    const [rectificationOuverte, setRectificationOuverte] = useState(false);

    const demanderRectification = (event: React.FormEvent) => {
        event.preventDefault();
        rectificationForm.post(contrats.rectification(contrat.id).url, {
            preserveScroll: true,
        });
    };
    const [apercu, setApercu] = useState<string | null>(null);

    useEffect(() => {
        if (form.data.signature === null) {
            setApercu(null);

            return;
        }

        const url = URL.createObjectURL(form.data.signature);
        setApercu(url);

        return () => URL.revokeObjectURL(url);
    }, [form.data.signature]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(contrats.signer(contrat.id).url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Contrat ${contrat.reference}`} />

            <Link
                href={contratsIndex()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Tous mes contrats
            </Link>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                        {contrat.objet}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        Réf. {contrat.reference} · Code de vérification{' '}
                        {contrat.codeVerification}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13 }}
                >
                    {STATUT_LABEL[contrat.statut] ?? contrat.statut}
                </span>
            </div>

            {contrat.montant && (
                <div className="card" style={{ padding: 20, marginBottom: 24 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Montant du contrat
                    </p>
                    <p
                        style={{
                            fontSize: 22,
                            fontWeight: 800,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        {Number(contrat.montant).toLocaleString('fr-FR')} FCFA
                    </p>
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <h2 style={{ fontSize: 15, marginBottom: 12 }}>
                    Texte du contrat
                </h2>
                <DocumentContrat contenu={contrat.contenu} />
            </div>

            {contrat.estSigne ? (
                <div
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                    }}
                >
                    <div>
                        <h2 style={{ fontSize: 15, marginBottom: 6 }}>
                            Contrat signé
                        </h2>
                        <p className="muted" style={{ fontSize: 13.5 }}>
                            Signé par {contrat.signataireNom ?? '—'} le{' '}
                            {contrat.dateSignature ?? '—'}.
                        </p>
                    </div>
                    <a
                        href={contrats.pdf(contrat.id).url}
                        className="btn-primary"
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: 8,
                        }}
                    >
                        <Download size={16} /> Télécharger le PDF signé
                    </a>
                </div>
            ) : contrat.peutSigner ? (
                <form
                    onSubmit={submit}
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 18,
                    }}
                >
                    <div>
                        <h2
                            style={{
                                fontSize: 16,
                                display: 'flex',
                                alignItems: 'center',
                                gap: 8,
                                marginBottom: 6,
                            }}
                        >
                            <FileSignature size={18} /> Signer le contrat
                        </h2>
                        <p className="muted" style={{ fontSize: 13.5 }}>
                            Importez une photo ou un scan de votre signature
                            manuscrite (PNG ou JPG). La date, l'heure et votre
                            adresse IP sont conservées comme preuve de
                            signature.
                        </p>
                    </div>

                    {identite.exigee && (
                        <fieldset
                            style={{
                                border: 'none',
                                margin: 0,
                                padding: 0,
                            }}
                        >
                            <legend
                                style={{
                                    fontSize: 14,
                                    fontWeight: 700,
                                    marginBottom: 4,
                                }}
                            >
                                Vos informations
                            </legend>
                            <p
                                className="muted"
                                style={{ fontSize: 12.5, marginBottom: 12 }}
                            >
                                Elles figurent en tête du contrat signé, comme
                                le prévoit le contrat DAG.
                            </p>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns:
                                        'repeat(auto-fit, minmax(220px, 1fr))',
                                    gap: 14,
                                }}
                            >
                                {CHAMPS_IDENTITE.map((champ) => (
                                    <div key={champ.nom}>
                                        <label
                                            htmlFor={`identite-${champ.nom}`}
                                        >
                                            {champ.libelle}
                                        </label>
                                        <input
                                            id={`identite-${champ.nom}`}
                                            type={champ.type ?? 'text'}
                                            placeholder={champ.placeholder}
                                            autoComplete={champ.autoComplete}
                                            max={
                                                champ.type === 'date'
                                                    ? new Date()
                                                          .toISOString()
                                                          .slice(0, 10)
                                                    : undefined
                                            }
                                            value={form.data[champ.nom]}
                                            onChange={(event) =>
                                                form.setData(
                                                    champ.nom,
                                                    event.target.value,
                                                )
                                            }
                                            required
                                        />
                                        {form.errors[champ.nom] && (
                                            <p
                                                style={{
                                                    color: 'var(--dag-red)',
                                                    fontSize: 12.5,
                                                    marginTop: 6,
                                                }}
                                            >
                                                {form.errors[champ.nom]}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </fieldset>
                    )}

                    <div>
                        <label htmlFor="signature">Votre signature</label>
                        <label
                            htmlFor="signature"
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 8,
                                minHeight: 140,
                                marginTop: 6,
                                border: '2px dashed var(--dag-border)',
                                borderRadius: 14,
                                background: '#f7f9fe',
                                cursor: 'pointer',
                                textAlign: 'center',
                                padding: 14,
                            }}
                        >
                            {apercu ? (
                                <img
                                    src={apercu}
                                    alt="Aperçu de la signature"
                                    style={{ maxHeight: 110, maxWidth: '100%' }}
                                />
                            ) : (
                                <>
                                    <ImagePlus size={26} />
                                    <span style={{ fontSize: 13.5 }}>
                                        Cliquez pour importer l'image de votre
                                        signature
                                    </span>
                                </>
                            )}
                        </label>
                        <input
                            id="signature"
                            type="file"
                            accept="image/png,image/jpeg"
                            style={{ display: 'none' }}
                            onChange={(event) =>
                                form.setData(
                                    'signature',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {form.errors.signature && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12.5,
                                    marginTop: 6,
                                }}
                            >
                                {form.errors.signature}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="nom-signataire">
                            Nom complet du signataire
                        </label>
                        <input
                            id="nom-signataire"
                            value={form.data.nom}
                            onChange={(event) =>
                                form.setData('nom', event.target.value)
                            }
                            required
                        />
                        {form.errors.nom && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12.5,
                                    marginTop: 6,
                                }}
                            >
                                {form.errors.nom}
                            </p>
                        )}
                    </div>

                    <label
                        style={{
                            display: 'flex',
                            gap: 10,
                            alignItems: 'flex-start',
                            fontSize: 13.5,
                            cursor: 'pointer',
                        }}
                    >
                        <input
                            type="checkbox"
                            checked={form.data.accepte}
                            onChange={(event) =>
                                form.setData('accepte', event.target.checked)
                            }
                            style={{ marginTop: 3 }}
                        />
                        J'ai lu le contrat ci-dessus et j'accepte ses termes.
                    </label>
                    {form.errors.accepte && (
                        <p style={{ color: 'var(--dag-red)', fontSize: 12.5 }}>
                            {form.errors.accepte}
                        </p>
                    )}

                    <div>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={
                                form.processing ||
                                form.data.signature === null ||
                                !form.data.accepte
                            }
                        >
                            {form.processing ? 'Envoi…' : 'Signer le contrat'}
                        </button>
                    </div>
                </form>
            ) : (
                <div className="card" style={{ padding: 24 }}>
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Ce contrat n'est pas ouvert à la signature.
                    </p>
                </div>
            )}

            {contrat.peutSigner && identite.exigee && (
                <div className="card" style={{ padding: 24, marginTop: 24 }}>
                    <h2 style={{ fontSize: 15, marginBottom: 6 }}>
                        Un point à corriger avant de signer ?
                    </h2>
                    {rectification.peutDemander ? (
                        <>
                            <p
                                className="muted"
                                style={{ fontSize: 13.5, lineHeight: 1.6 }}
                            >
                                Vous pouvez demander une rectification du
                                contrat : il vous reste{' '}
                                <strong>
                                    {rectification.restantes} demande
                                    {rectification.restantes > 1 ? 's' : ''}
                                </strong>{' '}
                                sur {rectification.max}. Au-delà, un nouveau
                                contrat devra être établi par DAG.
                            </p>
                            {rectificationOuverte ? (
                                <form
                                    onSubmit={demanderRectification}
                                    style={{
                                        marginTop: 14,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: 10,
                                    }}
                                >
                                    <label htmlFor="motif-rectification">
                                        Ce qui doit être rectifié
                                    </label>
                                    <textarea
                                        id="motif-rectification"
                                        rows={4}
                                        placeholder="Ex. Le nombre de badges est de 120 et non 100."
                                        value={rectificationForm.data.motif}
                                        onChange={(event) =>
                                            rectificationForm.setData(
                                                'motif',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {rectificationForm.errors.motif && (
                                        <p
                                            style={{
                                                color: 'var(--dag-red)',
                                                fontSize: 12.5,
                                            }}
                                        >
                                            {rectificationForm.errors.motif}
                                        </p>
                                    )}
                                    <div style={{ display: 'flex', gap: 10 }}>
                                        <button
                                            type="submit"
                                            className="btn-primary"
                                            disabled={
                                                rectificationForm.processing
                                            }
                                        >
                                            {rectificationForm.processing
                                                ? 'Envoi…'
                                                : 'Envoyer la demande'}
                                        </button>
                                        <button
                                            type="button"
                                            className="btn-outline"
                                            onClick={() =>
                                                setRectificationOuverte(false)
                                            }
                                        >
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <button
                                    type="button"
                                    className="btn-outline"
                                    style={{ marginTop: 14 }}
                                    onClick={() =>
                                        setRectificationOuverte(true)
                                    }
                                >
                                    Demander une rectification
                                </button>
                            )}
                        </>
                    ) : (
                        <p
                            className="muted"
                            style={{ fontSize: 13.5, lineHeight: 1.6 }}
                        >
                            Vous avez utilisé vos {rectification.max} demandes
                            de rectification. Pour toute autre modification,
                            contactez l'équipe DAG : un nouveau contrat sera
                            établi.
                        </p>
                    )}
                </div>
            )}
        </>
    );
}
