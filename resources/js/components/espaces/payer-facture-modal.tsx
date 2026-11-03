import { useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import paiements from '@/routes/paiements';

export type MoyenPaiement = { value: string; label: string; mobile: boolean };

type Props = {
    facture: { id: string; reference: string; objet: string; solde: string };
    moyens: MoyenPaiement[];
    onClose: () => void;
};

export default function PayerFactureModal({ facture, moyens, onClose }: Props) {
    const form = useForm({
        moyen: moyens[0]?.value ?? '',
        telephone: '',
    });

    const moyenChoisi = moyens.find((moyen) => moyen.value === form.data.moyen);

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
            aria-label="Payer la facture"
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
                    Payer la facture {facture.reference}
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 20,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    {facture.objet}
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
                        <p
                            style={{
                                fontSize: 13,
                                fontWeight: 600,
                                marginBottom: 8,
                            }}
                        >
                            Moyen de paiement
                        </p>
                        <div
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 8,
                            }}
                        >
                            {moyens.map((moyen) => (
                                <label
                                    key={moyen.value}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                        padding: '11px 14px',
                                        border:
                                            form.data.moyen === moyen.value
                                                ? '2px solid var(--dag-blue)'
                                                : '1px solid var(--dag-border)',
                                        borderRadius: 10,
                                        cursor: 'pointer',
                                        fontSize: 14,
                                        fontWeight: 600,
                                    }}
                                >
                                    <input
                                        type="radio"
                                        name="moyen"
                                        value={moyen.value}
                                        checked={form.data.moyen === moyen.value}
                                        onChange={() =>
                                            form.setData('moyen', moyen.value)
                                        }
                                    />
                                    {moyen.label}
                                </label>
                            ))}
                        </div>
                    </div>

                    {moyenChoisi?.mobile && (
                        <div>
                            <label htmlFor="telephone-paiement">
                                Numéro {moyenChoisi.label}
                            </label>
                            <input
                                id="telephone-paiement"
                                type="tel"
                                inputMode="tel"
                                placeholder="6 90 00 00 00"
                                value={form.data.telephone}
                                onChange={(event) =>
                                    form.setData('telephone', event.target.value)
                                }
                                required
                            />
                            {form.errors.telephone && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.telephone}
                                </p>
                            )}
                            <p
                                className="muted"
                                style={{ fontSize: 12, marginTop: 6 }}
                            >
                                Vous recevrez une demande de confirmation sur ce
                                numéro.
                            </p>
                        </div>
                    )}

                    {form.errors.moyen && (
                        <p style={{ color: 'var(--dag-red)', fontSize: 12.5 }}>
                            {form.errors.moyen}
                        </p>
                    )}

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
                        >
                            Payer {Number(facture.solde).toLocaleString('fr-FR')}{' '}
                            FCFA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
