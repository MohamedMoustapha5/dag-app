import { Head, Link } from '@inertiajs/react';
import { logout } from '@/routes';

type Props = {
    apprenant: {
        ville: string | null;
        inscriptionsCount: number;
        candidaturesCount: number;
    };
};

export default function ApprenantDashboard({ apprenant }: Props) {
    return (
        <>
            <Head title="Espace apprenant" />
            <div className="mx-auto flex min-h-svh max-w-xl flex-col justify-center gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">
                        Espace apprenant DAG
                    </p>
                    <h1 className="text-2xl font-semibold">
                        Bienvenue dans votre espace
                    </h1>
                </div>
                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <dt className="text-muted-foreground">Ville</dt>
                    <dd>{apprenant.ville ?? '—'}</dd>
                    <dt className="text-muted-foreground">Inscriptions</dt>
                    <dd>{apprenant.inscriptionsCount}</dd>
                    <dt className="text-muted-foreground">Candidatures</dt>
                    <dd>{apprenant.candidaturesCount}</dd>
                </dl>
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
