import { Head, Link, router } from '@inertiajs/react';
import espaceAdmin from '@/routes/espace-admin';
import attestationsAdmin from '@/routes/espace-admin/attestations';

type EnAttente = {
    inscriptionId: string;
    apprenantNom: string;
    formation: string;
};

type Validee = {
    id: string;
    reference: string;
    apprenantNom: string;
    formation: string;
    dateEmission: string;
};

type Props = {
    enAttente: EnAttente[];
    validees: Validee[];
};

export default function AdminAttestations({ enAttente, validees }: Props) {
    const valider = (inscriptionId: string) => {
        router.post(
            attestationsAdmin.store().url,
            { inscription_id: inscriptionId },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Attestations — Administration" />
            <div className="mx-auto flex min-h-svh max-w-3xl flex-col gap-6 p-6">
                <div>
                    <Link
                        href={espaceAdmin.dashboard()}
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Espace administrateur
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold">
                        Attestations
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Une attestation n'est jamais délivrée automatiquement :
                        chaque formation terminée doit être validée ici par un
                        membre habilité de l'équipe DAG avant que l'apprenant
                        n'y ait accès.
                    </p>
                </div>

                <div>
                    <p className="mb-2 text-sm font-semibold">
                        En attente de validation ({enAttente.length})
                    </p>
                    {enAttente.length === 0 ? (
                        <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                            Aucune formation terminée en attente de validation.
                        </p>
                    ) : (
                        <div className="divide-y rounded-xl border">
                            {enAttente.map((item) => (
                                <div
                                    key={item.inscriptionId}
                                    className="flex items-center justify-between gap-3 p-3 text-sm"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {item.apprenantNom}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {item.formation}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            valider(item.inscriptionId)
                                        }
                                        className="shrink-0 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
                                    >
                                        Valider et générer
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <p className="mb-2 text-sm font-semibold">
                        Attestations validées ({validees.length})
                    </p>
                    {validees.length === 0 ? (
                        <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                            Aucune attestation validée pour le moment.
                        </p>
                    ) : (
                        <div className="divide-y rounded-xl border">
                            {validees.map((attestation) => (
                                <div
                                    key={attestation.id}
                                    className="flex items-center justify-between gap-3 p-3 text-sm"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {attestation.apprenantNom}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {attestation.formation} —{' '}
                                            {attestation.reference}
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        Validée le {attestation.dateEmission}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
