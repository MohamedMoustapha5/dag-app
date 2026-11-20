import { Head, useForm } from '@inertiajs/react';
import motDePasse from '@/routes/espace-admin/mot-de-passe';

const CHAMPS = [
    {
        nom: 'current_password',
        label: 'Mot de passe actuel',
        autoComplete: 'current-password',
    },
    {
        nom: 'password',
        label: 'Nouveau mot de passe',
        autoComplete: 'new-password',
    },
    {
        nom: 'password_confirmation',
        label: 'Confirmer le nouveau mot de passe',
        autoComplete: 'new-password',
    },
] as const;

export default function AdminMotDePasse() {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(motDePasse.update().url, {
            preserveScroll: true,
            onFinish: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Mot de passe — Administration" />

            <h1 style={{ fontSize: 22, marginBottom: 6 }}>Mon mot de passe</h1>
            <p className="muted" style={{ fontSize: 13.5, marginBottom: 24 }}>
                Changer votre mot de passe déconnecte toutes vos autres sessions
                ouvertes.
            </p>

            <form
                onSubmit={submit}
                className="card"
                style={{
                    padding: 24,
                    maxWidth: 420,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 14,
                }}
            >
                {CHAMPS.map((champ) => (
                    <div key={champ.nom}>
                        <label htmlFor={champ.nom}>{champ.label}</label>
                        <input
                            id={champ.nom}
                            type="password"
                            required
                            autoComplete={champ.autoComplete}
                            value={form.data[champ.nom]}
                            onChange={(event) =>
                                form.setData(champ.nom, event.target.value)
                            }
                        />
                        {form.errors[champ.nom] && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors[champ.nom]}
                            </p>
                        )}
                    </div>
                ))}
                <button
                    type="submit"
                    disabled={form.processing}
                    className="btn-primary"
                    style={{ alignSelf: 'flex-start' }}
                >
                    {form.processing
                        ? 'Enregistrement…'
                        : 'Changer le mot de passe'}
                </button>
            </form>
        </>
    );
}
