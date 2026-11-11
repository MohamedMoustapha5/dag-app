import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ProchaineEtapeCourte } from '@/components/espaces/suivi-procedure';
import demandesRoutes from '@/routes/espace-client/demandes';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Demande = {
    id: string;
    reference: string;
    objet: string;
    statut: string;
    statutLabel: string | null;
    estPartenaire: boolean;
    updatedAt: string;
    prochaineEtape: { titre: string; aVous: boolean } | null;
};

type Props = {
    demandes: Demande[];
};

type Filtre =
    'a-faire' | 'toutes' | 'en-cours' | 'devis-envoye' | 'transmis' | 'termine';

const FILTRES: { value: Filtre; label: string }[] = [
    { value: 'a-faire', label: 'À faire de votre côté' },
    { value: 'toutes', label: 'Toutes' },
    { value: 'en-cours', label: 'En cours' },
    { value: 'devis-envoye', label: 'Devis envoyé' },
    { value: 'transmis', label: 'Transmis' },
    { value: 'termine', label: 'Terminé' },
];

function miseAJourLabel(dateIso: string): string {
    const date = new Date(dateIso);
    const aujourdhui = new Date();
    const hier = new Date(aujourdhui);
    hier.setDate(hier.getDate() - 1);

    if (date.toDateString() === aujourdhui.toDateString()) {
        return "Aujourd'hui";
    }
    if (date.toDateString() === hier.toDateString()) {
        return 'Hier';
    }
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date);
}

export default function DemandesIndex({ demandes }: Props) {
    const [filtre, setFiltre] = useState<Filtre>(() =>
        demandes.some((demande) => demande.prochaineEtape?.aVous)
            ? 'a-faire'
            : 'toutes',
    );

    const demandesFiltrees = useMemo(() => {
        return demandes.filter((demande) => {
            switch (filtre) {
                case 'a-faire':
                    return demande.prochaineEtape?.aVous === true;
                case 'en-cours':
                    return (
                        demande.statut === 'EN_COURS' && !demande.estPartenaire
                    );
                case 'devis-envoye':
                    return demande.statut === 'DEVIS_ENVOYE';
                case 'transmis':
                    return demande.estPartenaire;
                case 'termine':
                    return demande.statut === 'TERMINEE';
                default:
                    return true;
            }
        });
    }, [demandes, filtre]);

    return (
        <>
            <Head title="Mes demandes" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Mes demandes</h1>
                        <p>
                            Suivi du statut de chaque demande, reçue jusqu'à
                            terminée.
                        </p>
                    </div>
                    <Link
                        href={demandesRoutes.create()}
                        className="btn-primary"
                    >
                        <Plus size={16} />
                        Nouvelle demande
                    </Link>
                </div>
            </div>

            <div className="status-tabs">
                {FILTRES.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        className={`pill${filtre === item.value ? ' active' : ''}`}
                        onClick={() => setFiltre(item.value)}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            <div className="data-table-wrap">
                {demandesFiltrees.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune demande dans cette catégorie.
                    </p>
                ) : (
                    <table className="data-table cartes-mobile">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Prochaine étape</th>
                                <th>Mise à jour</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {demandesFiltrees.map((demande) => (
                                <tr key={demande.id}>
                                    <td
                                        className="cellule-titre"
                                        data-label="Service"
                                        style={{ fontWeight: 600 }}
                                    >
                                        {demande.objet}
                                    </td>
                                    <td data-label="Type" className="muted">
                                        {demande.estPartenaire
                                            ? 'Nova-Tech'
                                            : 'DAG'}
                                    </td>
                                    <td data-label="Statut">
                                        <span
                                            className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                                        >
                                            {demande.statutLabel ??
                                                STATUT_LABEL[demande.statut] ??
                                                demande.statut}
                                        </span>
                                    </td>
                                    <td data-label="Prochaine étape">
                                        <ProchaineEtapeCourte
                                            prochaine={demande.prochaineEtape}
                                        />
                                    </td>
                                    <td
                                        data-label="Mise à jour"
                                        className="muted"
                                    >
                                        {miseAJourLabel(demande.updatedAt)}
                                    </td>
                                    <td
                                        data-label=""
                                        style={{ textAlign: 'right' }}
                                    >
                                        <Link
                                            href={demandesRoutes.show(
                                                demande.id,
                                            )}
                                            className="link-action"
                                        >
                                            Voir le suivi
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}
