import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { formatMontant } from '@/lib/montant';
import recrutementAdmin from '@/routes/espace-admin/recrutement';

type Forfait = {
    id: string;
    libelle: string;
    dureeMois: number;
    prix: string;
};

type Etat = 'EN_COURS' | 'A_VENIR' | 'EXPIRE' | 'EN_ATTENTE';

type Acces = {
    id: string;
    clientNom: string;
    libelle: string;
    origine: 'PAIEMENT' | 'OFFERT';
    offertPar: string | null;
    montant: string;
    debut: string | null;
    fin: string | null;
    factureReference: string | null;
    etat: Etat;
};

type Props = {
    forfaits: Forfait[];
    acces: Acces[];
    peutModifier: boolean;
};

const ETAT: Record<Etat, { label: string; chip: string }> = {
    EN_COURS: { label: 'En cours', chip: 'chip-green' },
    A_VENIR: { label: 'À venir', chip: 'chip-blue' },
    EXPIRE: { label: 'Expiré', chip: 'chip-gray' },
    EN_ATTENTE: { label: 'En attente de paiement', chip: 'chip-orange' },
};

function dateCourte(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR');
}

export default function AdminRecrutement({
    forfaits,
    acces,
    peutModifier,
}: Props) {
    const [editionId, setEditionId] = useState<string | null>(null);

    return (
        <>
            <Head title="Abonnements recrutement — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/handshake-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Abonnements recrutement</h1>
                    <p>
                        Les entreprises et organisations accèdent à leurs pages
                        recrutement avec un abonnement payé en ligne.
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                    Forfaits proposés
                </p>
                <p className="muted" style={{ fontSize: 13, marginBottom: 14 }}>
                    {peutModifier
                        ? 'Un nouveau prix s’applique aux abonnements choisis ensuite ; une facture déjà émise garde son montant.'
                        : 'Lecture seule : votre rôle ne permet pas de modifier les forfaits.'}
                </p>
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Forfait</th>
                                <th>Durée</th>
                                <th>Prix</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {forfaits.map((forfait) => (
                                <ForfaitLigne
                                    key={forfait.id}
                                    forfait={forfait}
                                    peutModifier={peutModifier}
                                    enEdition={editionId === forfait.id}
                                    onEditer={() => setEditionId(forfait.id)}
                                    onFermer={() => setEditionId(null)}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 14,
                    }}
                >
                    Accès recrutement ({acces.length})
                </p>
                {acces.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun abonnement pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Durée</th>
                                    <th>Période</th>
                                    <th>Montant</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                {acces.map((ligne) => (
                                    <tr key={ligne.id}>
                                        <td
                                            data-label="Client"
                                            style={{ fontWeight: 600 }}
                                        >
                                            {ligne.clientNom}
                                        </td>
                                        <td data-label="Durée">
                                            {ligne.libelle}
                                        </td>
                                        <td
                                            data-label="Période"
                                            className="muted"
                                        >
                                            {ligne.debut && ligne.fin
                                                ? `${dateCourte(ligne.debut)} → ${dateCourte(ligne.fin)}`
                                                : '—'}
                                        </td>
                                        <td data-label="Montant">
                                            {ligne.origine === 'OFFERT' ? (
                                                <span className="muted">
                                                    Offert
                                                    {ligne.offertPar
                                                        ? ` par ${ligne.offertPar}`
                                                        : ''}
                                                </span>
                                            ) : (
                                                <>
                                                    {formatMontant(
                                                        ligne.montant,
                                                    )}
                                                    {ligne.factureReference && (
                                                        <p
                                                            className="muted"
                                                            style={{
                                                                fontSize: 12,
                                                            }}
                                                        >
                                                            {
                                                                ligne.factureReference
                                                            }
                                                        </p>
                                                    )}
                                                </>
                                            )}
                                        </td>
                                        <td data-label="État">
                                            <span
                                                className={`chip ${ETAT[ligne.etat].chip}`}
                                            >
                                                {ETAT[ligne.etat].label}
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
    );
}

function ForfaitLigne({
    forfait,
    peutModifier,
    enEdition,
    onEditer,
    onFermer,
}: {
    forfait: Forfait;
    peutModifier: boolean;
    enEdition: boolean;
    onEditer: () => void;
    onFermer: () => void;
}) {
    const form = useForm({
        libelle: forfait.libelle,
        prix: String(Math.round(Number(forfait.prix))),
    });

    const enregistrer = (event: React.FormEvent) => {
        event.preventDefault();
        form.patch(recrutementAdmin.forfaits.update(forfait.id).url, {
            preserveScroll: true,
            onSuccess: onFermer,
        });
    };

    if (enEdition) {
        return (
            <tr>
                <td colSpan={4}>
                    <form
                        onSubmit={enregistrer}
                        style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            alignItems: 'flex-start',
                            gap: 10,
                        }}
                    >
                        <div style={{ flex: 1, minWidth: 140 }}>
                            <input
                                aria-label="Libellé"
                                value={form.data.libelle}
                                onChange={(event) =>
                                    form.setData('libelle', event.target.value)
                                }
                            />
                            {form.errors.libelle && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.libelle}
                                </p>
                            )}
                        </div>
                        <div style={{ flex: 1, minWidth: 140 }}>
                            <input
                                aria-label="Prix (FCFA)"
                                type="number"
                                min={100}
                                step={1}
                                value={form.data.prix}
                                onChange={(event) =>
                                    form.setData('prix', event.target.value)
                                }
                            />
                            {form.errors.prix && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.prix}
                                </p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="btn-primary"
                            style={{ padding: '7px 14px', fontSize: 12.5 }}
                        >
                            Enregistrer
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                form.reset();
                                form.clearErrors();
                                onFermer();
                            }}
                            className="btn-outline"
                            style={{ padding: '7px 14px', fontSize: 12.5 }}
                        >
                            Annuler
                        </button>
                    </form>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td style={{ fontWeight: 600 }}>{forfait.libelle}</td>
            <td className="muted">{forfait.dureeMois} mois</td>
            <td>{formatMontant(forfait.prix)}</td>
            <td style={{ textAlign: 'right' }}>
                {peutModifier && (
                    <button
                        type="button"
                        onClick={onEditer}
                        className="link-action"
                    >
                        Modifier
                    </button>
                )}
            </td>
        </tr>
    );
}
