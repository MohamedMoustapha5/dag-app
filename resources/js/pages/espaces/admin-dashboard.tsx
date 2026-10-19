import { Head, Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { logout } from '@/routes';

type Props = {
    membre: {
        role: string;
        roleLabel: string;
    };
    droits: Record<string, string>;
};

const DONNEE_LABELS: Record<string, string> = {
    CATALOGUE: 'Catalogue de services',
    FORMATIONS: 'Formations & sessions',
    DEMANDES_CLIENTS: 'Demandes clients',
    ABONNEMENTS: 'Abonnements',
    FACTURES_PAIEMENTS: 'Factures & paiements',
    CONTRATS_CLAUSES: 'Contrats & clauses',
    DOSSIERS_APPRENANTS: 'Dossiers apprenants',
    ENTREPRISES_OFFRES: 'Entreprises partenaires & offres',
    STATISTIQUES: 'Statistiques',
};

export default function AdminDashboard({ membre, droits }: Props) {
    return (
        <>
            <Head title="Espace administrateur" />
            <div className="mx-auto flex min-h-svh max-w-xl flex-col justify-center gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">
                        Espace administrateur DAG
                    </p>
                    <h1 className="text-2xl font-semibold">
                        Connecté en tant que {membre.roleLabel}
                    </h1>
                </div>
                <div className="rounded-xl border p-4 text-sm">
                    <p className="mb-3 font-medium">Vos droits d'accès</p>
                    <dl className="grid grid-cols-2 gap-y-2">
                        {Object.entries(droits).map(([donnee, niveau]) => (
                            <Fragment key={donnee}>
                                <dt className="text-muted-foreground">
                                    {DONNEE_LABELS[donnee] ?? donnee}
                                </dt>
                                <dd>{niveau}</dd>
                            </Fragment>
                        ))}
                    </dl>
                </div>
                <Link
                    href={logout()}
                    as="button"
                    className="self-start text-sm text-muted-foreground underline"
                >
                    Déconnexion
                </Link>
            </div>
        </>
    );
}
