import { Head, Link } from '@inertiajs/react';
import { logout } from '@/routes';

type Props = {
    client: {
        nom: string;
        type: string;
        statut: string;
    };
};

export default function ClientDashboard({ client }: Props) {
    return (
        <>
            <Head title="Espace client" />
            <div className="mx-auto flex min-h-svh max-w-xl flex-col justify-center gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">
                        Espace client DAG
                    </p>
                    <h1 className="text-2xl font-semibold">
                        Bonjour {client.nom}
                    </h1>
                </div>
                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <dt className="text-muted-foreground">Type de compte</dt>
                    <dd>{client.type}</dd>
                    <dt className="text-muted-foreground">Statut</dt>
                    <dd>{client.statut}</dd>
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
