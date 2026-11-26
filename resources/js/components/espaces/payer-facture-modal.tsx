import { useForm } from '@inertiajs/react';
import { Check, CreditCard, Lock, ShieldCheck, X } from 'lucide-react';
import paiements from '@/routes/paiements';

export type MoyenPaiement = {
    value: string;
    label: string;
    mobile: boolean;
    /** Whether DAG asks the number; false when the provider's page does. */
    telephone: boolean;
};

type Props = {
    facture: { id: string; reference: string; objet: string; solde: string };
    moyens: MoyenPaiement[];
    onClose: () => void;
};

const NAVY = 'var(--dag-navy)';

/**
 * Brand-coloured badge for each payment method (initials on the operator's
 * colour, an icon for the card) — simple tiles, not the operators' logos.
 */
function BadgeMoyen({ moyen }: { moyen: string }) {
    const base = {
        width: 46,
        height: 46,
        borderRadius: 12,
        display: 'grid',
        placeItems: 'center',
        fontWeight: 800,
        fontSize: 15,
        letterSpacing: 0.3,
        flex: 'none',
    } as const;

    if (moyen === 'OM') {
        return (
            <span style={{ ...base, background: '#ff7900', color: '#fff' }}>
                OM
            </span>
        );
    }

    if (moyen === 'MTN_MOMO') {
        return (
            <span style={{ ...base, background: '#ffcc00', color: '#1a1a1a' }}>
                MTN
            </span>
        );
    }

    return (
        <span style={{ ...base, background: NAVY, color: '#fff' }}>
            <CreditCard size={22} />
        </span>
    );
}

/** Simple marks of the card networks accepted (not the brands' artwork). */
function CartesAcceptees() {
    const carte = {
        height: 30,
        minWidth: 48,
        padding: '0 8px',
        borderRadius: 6,
        border: '1px solid var(--dag-border)',
        background: '#fff',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
    } as const;

    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: 8,
                flexWrap: 'wrap',
            }}
        >
            <span className="muted" style={{ fontSize: 12 }}>
                Cartes acceptées
            </span>
            <span
                style={{
                    ...carte,
                    color: '#1a1f71',
                    fontWeight: 900,
                    fontStyle: 'italic',
                    fontSize: 14,
                    letterSpacing: 0.5,
                }}
                aria-label="Visa"
                translate="no"
            >
                VISA
            </span>
            <span style={carte} aria-label="Mastercard">
                <span
                    style={{
                        width: 16,
                        height: 16,
                        borderRadius: 999,
                        background: '#eb001b',
                    }}
                />
                <span
                    style={{
                        width: 16,
                        height: 16,
                        borderRadius: 999,
                        background: '#f79e1b',
                        marginLeft: -6,
                        mixBlendMode: 'multiply',
                    }}
                />
            </span>
        </div>
    );
}

const ETAPES_CARTE = [
    'Vous êtes redirigé vers la page de paiement sécurisée de notre prestataire.',
    'Vous saisissez le numéro de votre carte, sa date d’expiration et son code de sécurité (CVV).',
    'Votre banque vous demande de confirmer le paiement (code reçu par SMS ou validation dans son application).',
    'Vous revenez automatiquement sur votre espace DAG : votre facture est mise à jour.',
];

function PaiementCarte() {
    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                gap: 14,
                padding: '14px 16px',
                borderRadius: 12,
                background: '#f4f6fb',
            }}
        >
            <CartesAcceptees />
            <ol
                style={{
                    listStyle: 'none',
                    padding: 0,
                    margin: 0,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 9,
                }}
            >
                {ETAPES_CARTE.map((etape, index) => (
                    <li
                        key={etape}
                        style={{
                            display: 'flex',
                            gap: 10,
                            alignItems: 'flex-start',
                            fontSize: 12.5,
                            lineHeight: 1.5,
                            color: 'var(--dag-text)',
                        }}
                    >
                        <span
                            aria-hidden
                            style={{
                                width: 20,
                                height: 20,
                                borderRadius: 999,
                                background: 'var(--dag-blue)',
                                color: '#fff',
                                fontSize: 11,
                                fontWeight: 700,
                                display: 'grid',
                                placeItems: 'center',
                                flex: 'none',
                                marginTop: 1,
                            }}
                        >
                            {index + 1}
                        </span>
                        {etape}
                    </li>
                ))}
            </ol>
            <p
                style={{
                    display: 'flex',
                    gap: 8,
                    alignItems: 'flex-start',
                    fontSize: 12,
                    lineHeight: 1.5,
                    color: 'var(--dag-text-muted)',
                }}
            >
                <ShieldCheck
                    size={15}
                    style={{
                        flex: 'none',
                        marginTop: 1,
                        color: 'var(--dag-green)',
                    }}
                />
                Les informations de votre carte sont saisies uniquement chez le
                prestataire de paiement : DAG n’y a jamais accès.
            </p>
        </div>
    );
}

export default function PayerFactureModal({ facture, moyens, onClose }: Props) {
    const form = useForm({
        moyen: moyens[0]?.value ?? '',
        telephone: '',
    });

    const moyenChoisi = moyens.find((moyen) => moyen.value === form.data.moyen);
    const montant = Number(facture.solde).toLocaleString('fr-FR');
    // Mobile money confirmed on the provider's own page: no number here.
    const pageDuPrestataire =
        moyenChoisi !== undefined &&
        moyenChoisi.mobile &&
        !moyenChoisi.telephone;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(paiements.initier(facture.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Paiement sécurisé"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel" style={{ maxWidth: 520 }}>
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <p
                    style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: 6,
                        fontSize: 11.5,
                        fontWeight: 700,
                        letterSpacing: 1.2,
                        textTransform: 'uppercase',
                        color: 'var(--dag-blue)',
                        marginBottom: 6,
                    }}
                >
                    <Lock size={12} /> Paiement sécurisé
                </p>
                <h2 style={{ fontSize: 21, color: NAVY, marginBottom: 18 }}>
                    Régler la facture {facture.reference}
                </h2>

                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        padding: '16px 18px',
                        borderRadius: 14,
                        background: 'var(--dag-blue-light)',
                        marginBottom: 22,
                    }}
                >
                    <div style={{ minWidth: 0 }}>
                        <p
                            className="muted"
                            style={{ fontSize: 12, marginBottom: 2 }}
                        >
                            Prestation
                        </p>
                        <p
                            style={{
                                fontSize: 14,
                                fontWeight: 600,
                                color: NAVY,
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                            }}
                        >
                            {facture.objet}
                        </p>
                    </div>
                    <div style={{ textAlign: 'right', flex: 'none' }}>
                        <p
                            className="muted"
                            style={{ fontSize: 12, marginBottom: 2 }}
                        >
                            Montant à payer
                        </p>
                        <p
                            style={{
                                fontSize: 22,
                                fontWeight: 800,
                                color: 'var(--dag-blue)',
                                lineHeight: 1.1,
                            }}
                        >
                            {montant} <span style={{ fontSize: 13 }}>FCFA</span>
                        </p>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 22,
                    }}
                >
                    <fieldset style={{ border: 0, padding: 0, margin: 0 }}>
                        <legend
                            style={{
                                fontSize: 13,
                                fontWeight: 700,
                                color: NAVY,
                                marginBottom: 10,
                            }}
                        >
                            1. Choisissez votre moyen de paiement
                        </legend>
                        <div
                            style={{
                                display: 'grid',
                                gridTemplateColumns:
                                    'repeat(auto-fit, minmax(135px, 1fr))',
                                gap: 10,
                            }}
                        >
                            {moyens.map((moyen) => {
                                const actif = form.data.moyen === moyen.value;

                                return (
                                    <label
                                        key={moyen.value}
                                        style={{
                                            position: 'relative',
                                            display: 'flex',
                                            flexDirection: 'column',
                                            alignItems: 'center',
                                            gap: 10,
                                            padding: '16px 10px 14px',
                                            borderRadius: 14,
                                            border: actif
                                                ? '2px solid var(--dag-blue)'
                                                : '2px solid var(--dag-border)',
                                            background: actif
                                                ? 'var(--dag-blue-light)'
                                                : '#fff',
                                            cursor: 'pointer',
                                            textAlign: 'center',
                                            transition:
                                                'border-color .15s, background .15s',
                                        }}
                                    >
                                        <input
                                            type="radio"
                                            name="moyen"
                                            value={moyen.value}
                                            checked={actif}
                                            onChange={() =>
                                                form.setData(
                                                    'moyen',
                                                    moyen.value,
                                                )
                                            }
                                            style={{
                                                position: 'absolute',
                                                opacity: 0,
                                                inset: 0,
                                                cursor: 'pointer',
                                            }}
                                        />
                                        {actif && (
                                            <span
                                                style={{
                                                    position: 'absolute',
                                                    top: 8,
                                                    right: 8,
                                                    width: 20,
                                                    height: 20,
                                                    borderRadius: 999,
                                                    background:
                                                        'var(--dag-blue)',
                                                    color: '#fff',
                                                    display: 'grid',
                                                    placeItems: 'center',
                                                }}
                                            >
                                                <Check size={13} />
                                            </span>
                                        )}
                                        <BadgeMoyen moyen={moyen.value} />
                                        <span
                                            style={{
                                                fontSize: 13,
                                                fontWeight: 700,
                                                color: NAVY,
                                                lineHeight: 1.25,
                                            }}
                                        >
                                            {moyen.label}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                    </fieldset>

                    <div>
                        <p
                            style={{
                                fontSize: 13,
                                fontWeight: 700,
                                color: NAVY,
                                marginBottom: 10,
                            }}
                        >
                            2.{' '}
                            {pageDuPrestataire
                                ? 'Confirmation sur la page de paiement sécurisée'
                                : moyenChoisi?.mobile
                                  ? `Numéro ${moyenChoisi.label}`
                                  : 'Paiement par carte'}
                        </p>

                        {pageDuPrestataire ? (
                            <div
                                style={{
                                    display: 'flex',
                                    gap: 10,
                                    alignItems: 'flex-start',
                                    padding: '14px 16px',
                                    borderRadius: 12,
                                    background: '#f4f6fb',
                                    fontSize: 13,
                                    lineHeight: 1.55,
                                    color: 'var(--dag-text-muted)',
                                }}
                            >
                                <ShieldCheck
                                    size={18}
                                    style={{
                                        flex: 'none',
                                        marginTop: 1,
                                        color: 'var(--dag-green)',
                                    }}
                                />
                                <span>
                                    Vous allez être redirigé vers la page de
                                    paiement sécurisée de notre partenaire.
                                    Saisissez-y votre numéro{' '}
                                    {moyenChoisi?.label}, puis validez la
                                    demande reçue sur votre téléphone avec votre
                                    code secret. Vous revenez ensuite
                                    automatiquement sur DAG.
                                </span>
                            </div>
                        ) : moyenChoisi?.mobile ? (
                            <>
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'stretch',
                                        border: form.errors.telephone
                                            ? '1.5px solid var(--dag-red)'
                                            : '1.5px solid var(--dag-border)',
                                        borderRadius: 12,
                                        overflow: 'hidden',
                                        background: '#fff',
                                    }}
                                >
                                    <span
                                        style={{
                                            display: 'grid',
                                            placeItems: 'center',
                                            padding: '0 14px',
                                            background: '#f4f6fb',
                                            fontWeight: 700,
                                            fontSize: 14,
                                            color: NAVY,
                                            borderRight:
                                                '1.5px solid var(--dag-border)',
                                        }}
                                    >
                                        +237
                                    </span>
                                    <input
                                        id="telephone-paiement"
                                        type="tel"
                                        inputMode="tel"
                                        autoComplete="tel-national"
                                        aria-label={`Numéro ${moyenChoisi.label}`}
                                        placeholder="6 90 00 00 00"
                                        value={form.data.telephone}
                                        onChange={(event) =>
                                            form.setData(
                                                'telephone',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        style={{
                                            flex: 1,
                                            border: 0,
                                            outline: 'none',
                                            padding: '13px 14px',
                                            fontSize: 15,
                                            fontWeight: 600,
                                            background: 'transparent',
                                            minWidth: 0,
                                        }}
                                    />
                                </div>
                                {form.errors.telephone ? (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12.5,
                                            marginTop: 6,
                                        }}
                                    >
                                        {form.errors.telephone}
                                    </p>
                                ) : (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12.5, marginTop: 6 }}
                                    >
                                        Une demande de confirmation sera envoyée
                                        sur ce numéro : validez-la avec votre
                                        code secret.
                                    </p>
                                )}
                            </>
                        ) : (
                            <PaiementCarte />
                        )}
                    </div>

                    {form.errors.moyen && (
                        <p
                            style={{
                                color: 'var(--dag-red)',
                                fontSize: 12.5,
                                background: 'var(--dag-red-bg)',
                                padding: '10px 14px',
                                borderRadius: 10,
                            }}
                        >
                            {form.errors.moyen}
                        </p>
                    )}

                    <div>
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
                                disabled={form.processing || !moyenChoisi}
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 8,
                                }}
                            >
                                <Lock size={15} />
                                {form.processing
                                    ? 'Redirection…'
                                    : pageDuPrestataire ||
                                        (moyenChoisi && !moyenChoisi.mobile)
                                      ? `Payer ${montant} FCFA sur la page sécurisée`
                                      : `Payer ${montant} FCFA`}
                            </button>
                        </div>
                        <p
                            className="muted"
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 6,
                                fontSize: 11.5,
                                marginTop: 14,
                            }}
                        >
                            <ShieldCheck size={14} /> Transaction chiffrée. DAG
                            ne conserve aucune donnée bancaire.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    );
}
