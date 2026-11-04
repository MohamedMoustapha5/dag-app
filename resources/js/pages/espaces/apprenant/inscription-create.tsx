import { Head, Link, useForm } from '@inertiajs/react';
import { formations } from '@/routes';
import espaceApprenant from '@/routes/espace-apprenant';
import inscriptions from '@/routes/espace-apprenant/inscriptions';

type Session = {
    id: string;
    formation: string;
    categorie: string;
    ville: string | null;
    lieu: string | null;
    mode: string | null;
    formateur: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    placesRestantes: number;
    ouverte: boolean;
};

type Props = {
    session: Session;
};

export default function InscriptionCreate({ session }: Props) {
    const { post, processing } = useForm({});
    const disponible = session.ouverte && session.placesRestantes > 0;

    const lignes: [string, string | null][] = [
        ['Catégorie', session.categorie],
        ['Ville', session.ville],
        ['Lieu', session.lieu],
        ['Mode', session.mode],
        ['Formateur', session.formateur],
        ['Début', session.dateDebut],
        ['Fin', session.dateFin],
        ['Places restantes', String(session.placesRestantes)],
    ];

    return (
        <>
            <Head title="Confirmer mon inscription" />

            <div className="card" style={{ maxWidth: 640, margin: '0 auto' }}>
                <h1 style={{ fontSize: 22, marginBottom: 4 }}>
                    Confirmer mon inscription
                </h1>
                <p style={{ color: 'var(--dag-text-muted)', marginBottom: 20 }}>
                    {session.formation}
                </p>

                <dl
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'auto 1fr',
                        gap: '8px 20px',
                        margin: 0,
                    }}
                >
                    {lignes
                        .filter(([, valeur]) => valeur)
                        .map(([libelle, valeur]) => (
                            <div key={libelle} style={{ display: 'contents' }}>
                                <dt style={{ color: 'var(--dag-text-muted)' }}>
                                    {libelle}
                                </dt>
                                <dd style={{ margin: 0, fontWeight: 600 }}>
                                    {valeur}
                                </dd>
                            </div>
                        ))}
                </dl>

                {!disponible && (
                    <p
                        role="alert"
                        style={{ color: 'var(--dag-red)', marginTop: 20 }}
                    >
                        {session.ouverte
                            ? 'Cette session est complète.'
                            : "Les inscriptions à cette session ne sont pas ouvertes."}
                    </p>
                )}

                <p
                    style={{
                        marginTop: 20,
                        fontSize: 14,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Votre inscription sera enregistrée en attente de paiement.
                    L'équipe DAG vous contacte pour le règlement puis valide
                    votre place.
                </p>

                <div
                    style={{
                        display: 'flex',
                        gap: 12,
                        marginTop: 24,
                        flexWrap: 'wrap',
                    }}
                >
                    <button
                        type="button"
                        className="btn-primary"
                        disabled={!disponible || processing}
                        onClick={() => post(inscriptions.store(session.id).url)}
                    >
                        {processing
                            ? 'Enregistrement...'
                            : "Confirmer l'inscription"}
                    </button>
                    <Link href={formations()} className="btn-outline">
                        Retour aux formations
                    </Link>
                    <Link
                        href={espaceApprenant.inscriptions()}
                        className="btn-outline"
                    >
                        Mes inscriptions
                    </Link>
                </div>
            </div>
        </>
    );
}
