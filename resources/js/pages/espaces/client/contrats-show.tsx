import { Head, Link, useForm } from '@inertiajs/react';
import { Download, FileSignature, ImagePlus } from 'lucide-react';
import { useEffect, useState } from 'react';
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
};

export default function ContratsShow({ contrat }: Props) {
    const form = useForm<{
        signature: File | null;
        nom: string;
        accepte: boolean;
    }>({
        signature: null,
        nom: contrat.nomSuggere,
        accepte: false,
    });
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
                <div
                    className="card"
                    style={{ padding: 20, marginBottom: 24 }}
                >
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
                <p
                    style={{
                        fontSize: 13.5,
                        lineHeight: 1.7,
                        whiteSpace: 'pre-line',
                    }}
                >
                    {contrat.contenu ?? 'Le texte de ce contrat est en cours de rédaction.'}
                </p>
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
                            {form.processing
                                ? 'Envoi…'
                                : 'Signer le contrat'}
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
        </>
    );
}
