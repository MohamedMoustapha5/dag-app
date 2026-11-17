import { Head, router, useForm } from '@inertiajs/react';
import { Check, Mail, ShieldCheck, UserPlus, X } from 'lucide-react';
import { type EspaceClientInfo } from '@/layouts/espace-client-layout';
import collaborateursRoutes from '@/routes/espace-client/collaborateurs';

type Collaborateur = {
    id: number;
    nom: string;
    email: string;
    actif: boolean;
    depuis: string | null;
};

type Props = {
    client: EspaceClientInfo;
    titulaire: { nom: string; email: string };
    collaborateurs: Collaborateur[];
};

const PEUT = [
    'Faire des demandes et suivre les devis',
    'Payer les factures',
    'Signer les contrats et conventions',
    'Gérer les offres et les candidatures',
    'Consulter et télécharger les documents',
];

function initiales(nom: string): string {
    return nom
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join('');
}

function Erreur({ message }: { message?: string }) {
    return message ? (
        <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
            {message}
        </p>
    ) : null;
}

function Personne({
    nom,
    email,
    children,
}: {
    nom: string;
    email: string;
    children: React.ReactNode;
}) {
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: 14,
                padding: '16px 0',
                borderBottom: '1px solid var(--dag-border)',
                flexWrap: 'wrap',
            }}
        >
            <span
                aria-hidden
                style={{
                    width: 40,
                    height: 40,
                    borderRadius: '50%',
                    background: 'var(--dag-blue-light)',
                    color: 'var(--dag-blue)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontWeight: 700,
                    fontSize: 13.5,
                    flex: 'none',
                }}
            >
                {initiales(nom)}
            </span>
            <div style={{ flex: '1 1 180px', minWidth: 0 }}>
                <p style={{ fontWeight: 600, fontSize: 14 }}>{nom}</p>
                <p
                    className="muted"
                    style={{ fontSize: 12.5, overflowWrap: 'anywhere' }}
                >
                    {email}
                </p>
            </div>
            {children}
        </div>
    );
}

export default function Collaborateurs({
    client,
    titulaire,
    collaborateurs,
}: Props) {
    const form = useForm({ nom: '', email: '' });

    const inviter = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(collaborateursRoutes.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const renvoyer = (collaborateur: Collaborateur) => {
        router.post(
            collaborateursRoutes.renvoyer(collaborateur.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const retirer = (collaborateur: Collaborateur) => {
        if (
            !confirm(
                `Retirer l'accès de « ${collaborateur.nom} » ? Cette personne ne pourra plus se connecter à l'espace de votre structure. Les demandes, contrats et factures restent inchangés.`,
            )
        ) {
            return;
        }

        router.delete(collaborateursRoutes.destroy(collaborateur.id).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Accès & collaborateurs" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 6,
                }}
            >
                Accès & collaborateurs
            </h1>
            <p className="muted" style={{ fontSize: 13.5, marginBottom: 24 }}>
                {`Donnez accès à l'espace de ${client.nom} aux personnes qui le gèrent avec vous. Chacune se connecte avec son propre compte.`}
            </p>

            <div className="grid-3">
                <div
                    className="card"
                    style={{ padding: '8px 24px', gridColumn: 'span 2' }}
                >
                    <Personne nom={titulaire.nom} email={titulaire.email}>
                        <span className="chip chip-blue">
                            Titulaire du compte
                        </span>
                    </Personne>

                    {collaborateurs.map((collaborateur) => (
                        <Personne
                            key={collaborateur.id}
                            nom={collaborateur.nom}
                            email={collaborateur.email}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 14,
                                    flexWrap: 'wrap',
                                    justifyContent: 'flex-end',
                                }}
                            >
                                {collaborateur.actif ? (
                                    <span className="chip chip-green">
                                        {`Actif depuis le ${collaborateur.depuis}`}
                                    </span>
                                ) : (
                                    <>
                                        <span className="chip chip-orange">
                                            Invitation envoyée
                                        </span>
                                        <button
                                            type="button"
                                            className="link-action"
                                            onClick={() =>
                                                renvoyer(collaborateur)
                                            }
                                        >
                                            Renvoyer l'invitation
                                        </button>
                                    </>
                                )}
                                <button
                                    type="button"
                                    className="link-action"
                                    style={{ color: 'var(--dag-red)' }}
                                    onClick={() => retirer(collaborateur)}
                                >
                                    Retirer l'accès
                                </button>
                            </div>
                        </Personne>
                    ))}

                    {collaborateurs.length === 0 && (
                        <p
                            className="muted"
                            style={{
                                padding: '28px 0',
                                textAlign: 'center',
                                fontSize: 13.5,
                            }}
                        >
                            Aucun collaborateur pour le moment. Invitez une
                            personne de votre structure avec le formulaire.
                        </p>
                    )}
                </div>

                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 20,
                    }}
                >
                    <form
                        className="card"
                        style={{
                            padding: 24,
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 14,
                        }}
                        onSubmit={inviter}
                    >
                        <p
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 8,
                                fontWeight: 700,
                                fontSize: 14,
                            }}
                        >
                            <UserPlus size={18} /> Inviter un collaborateur
                        </p>
                        <div>
                            <label htmlFor="collaborateur-nom">
                                Nom complet
                            </label>
                            <input
                                id="collaborateur-nom"
                                value={form.data.nom}
                                onChange={(event) =>
                                    form.setData('nom', event.target.value)
                                }
                                required
                            />
                            <Erreur message={form.errors.nom} />
                        </div>
                        <div>
                            <label htmlFor="collaborateur-email">
                                Adresse e-mail professionnelle
                            </label>
                            <input
                                id="collaborateur-email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                required
                            />
                            <Erreur message={form.errors.email} />
                        </div>
                        <button
                            type="submit"
                            className="btn-primary btn-block"
                            disabled={form.processing}
                        >
                            <Mail size={16} />{' '}
                            {form.processing
                                ? 'Envoi…'
                                : "Envoyer l'invitation"}
                        </button>
                        <p className="muted" style={{ fontSize: 12 }}>
                            La personne reçoit un e-mail pour choisir son mot de
                            passe. Le lien est valable 7 jours.
                        </p>
                    </form>

                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 8,
                                fontWeight: 700,
                                fontSize: 14,
                                marginBottom: 12,
                            }}
                        >
                            <ShieldCheck size={18} /> Ce qu'un collaborateur
                            peut faire
                        </p>
                        <ul
                            style={{
                                listStyle: 'none',
                                padding: 0,
                                margin: 0,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 8,
                                fontSize: 13,
                            }}
                        >
                            {PEUT.map((ligne) => (
                                <li
                                    key={ligne}
                                    style={{
                                        display: 'flex',
                                        gap: 8,
                                        alignItems: 'flex-start',
                                    }}
                                >
                                    <Check
                                        size={15}
                                        style={{
                                            color: 'var(--dag-green)',
                                            flex: 'none',
                                            marginTop: 2,
                                        }}
                                    />
                                    {ligne}
                                </li>
                            ))}
                            <li
                                style={{
                                    display: 'flex',
                                    gap: 8,
                                    alignItems: 'flex-start',
                                }}
                            >
                                <X
                                    size={15}
                                    style={{
                                        color: 'var(--dag-red)',
                                        flex: 'none',
                                        marginTop: 2,
                                    }}
                                />
                                Gérer les accès : réservé au titulaire du compte
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}
