import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Eye,
    EyeOff,
    ExternalLink,
    ImagePlus,
    Pencil,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import logosAdmin from '@/routes/espace-admin/logos-partenaires';

type Logo = {
    id: string;
    nom: string;
    logo: string;
    lien: string | null;
    fondSombre: boolean;
    visible: boolean;
};

type Props = {
    logos: Logo[];
    actions: { creer: boolean; modifier: boolean; supprimer: boolean };
};

/** Address of a picked file, freed when another one is picked. */
function useApercu(fichier: File | null): string | null {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        if (fichier === null) {
            setUrl(null);

            return;
        }

        const adresse = URL.createObjectURL(fichier);
        setUrl(adresse);

        return () => URL.revokeObjectURL(adresse);
    }, [fichier]);

    return url;
}

/** A logo as the site shows it: on a white tile, or a dark one. */
function Tuile({
    src,
    nom,
    sombre,
}: {
    src: string;
    nom: string;
    sombre: boolean;
}) {
    return (
        <div
            className={`logo-defilant-carte logos-admin-tuile${sombre ? ' logo-defilant-carte--sombre' : ''}`}
        >
            <img src={src} alt={nom} draggable={false} />
        </div>
    );
}

export default function AdminLogosPartenaires({ logos, actions }: Props) {
    const visibles = logos.filter((logo) => logo.visible);

    return (
        <>
            <Head title="Logos partenaires — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/handshake-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Logos partenaires</h1>
                    <p>
                        Les logos affichés sur le site public : bande défilante
                        de l’accueil et page « À propos ».
                    </p>
                </div>
            </div>

            <div className="card logos-admin-apercu">
                <div className="logos-admin-apercu-tete">
                    <p className="logos-admin-titre">Aperçu sur le site</p>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        {visibles.length} logo
                        {visibles.length > 1 ? 's' : ''} affiché
                        {visibles.length > 1 ? 's' : ''}
                    </span>
                </div>
                {visibles.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun logo affiché : la rubrique « Nos partenaires » est
                        masquée sur le site.
                    </p>
                ) : (
                    <div className="logos-admin-apercu-bande">
                        {visibles.map((logo) => (
                            <Tuile
                                key={logo.id}
                                src={logo.logo}
                                nom={logo.nom}
                                sombre={logo.fondSombre}
                            />
                        ))}
                    </div>
                )}
            </div>

            {actions.creer && <FormulaireAjout />}

            {logos.length > 0 && (
                <div className="logos-admin-grille">
                    {logos.map((logo, index) => (
                        <CarteLogo
                            key={logo.id}
                            logo={logo}
                            premier={index === 0}
                            dernier={index === logos.length - 1}
                            actions={actions}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

function FormulaireAjout() {
    const form = useForm<{
        nom: string;
        lien: string;
        fond_sombre: boolean;
        logo: File | null;
    }>({ nom: '', lien: '', fond_sombre: false, logo: null });
    const apercu = useApercu(form.data.logo);
    const [cle, setCle] = useState(0);

    const envoyer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(logosAdmin.store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setCle((valeur) => valeur + 1);
            },
        });
    };

    return (
        <form onSubmit={envoyer} className="card logos-admin-ajout">
            <p className="logos-admin-titre">Ajouter un logo</p>
            <div className="logos-admin-ajout-corps">
                <label className="logos-admin-depot">
                    <input
                        key={cle}
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        onChange={(event) =>
                            form.setData(
                                'logo',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                    {apercu ? (
                        <Tuile
                            src={apercu}
                            nom={form.data.nom || 'Aperçu'}
                            sombre={form.data.fond_sombre}
                        />
                    ) : (
                        <span className="logos-admin-depot-vide">
                            <ImagePlus size={22} aria-hidden="true" />
                            Choisir le logo
                            <small>PNG, JPG ou WebP · 2 Mo max</small>
                        </span>
                    )}
                </label>

                <div className="logos-admin-champs">
                    <div>
                        <label htmlFor="logo-nom">Nom du partenaire</label>
                        <input
                            id="logo-nom"
                            value={form.data.nom}
                            onChange={(event) =>
                                form.setData('nom', event.target.value)
                            }
                            placeholder="Ex. Nordex Consulting Group"
                        />
                    </div>
                    <div>
                        <label htmlFor="logo-lien">
                            Site web (facultatif)
                        </label>
                        <input
                            id="logo-lien"
                            type="url"
                            value={form.data.lien}
                            onChange={(event) =>
                                form.setData('lien', event.target.value)
                            }
                            placeholder="https://"
                        />
                    </div>
                    <label className="logos-admin-case">
                        <input
                            type="checkbox"
                            checked={form.data.fond_sombre}
                            onChange={(event) =>
                                form.setData(
                                    'fond_sombre',
                                    event.target.checked,
                                )
                            }
                        />
                        Logo sur fond foncé (il remplit sa tuile)
                    </label>
                    {(form.errors.logo ||
                        form.errors.nom ||
                        form.errors.lien) && (
                        <p className="logos-admin-erreur">
                            {form.errors.logo ??
                                form.errors.nom ??
                                form.errors.lien}
                        </p>
                    )}
                    <button
                        type="submit"
                        className="btn-primary"
                        disabled={form.processing}
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing ? 'Ajout…' : 'Ajouter au site'}
                    </button>
                </div>
            </div>
        </form>
    );
}

function CarteLogo({
    logo,
    premier,
    dernier,
    actions,
}: {
    logo: Logo;
    premier: boolean;
    dernier: boolean;
    actions: Props['actions'];
}) {
    const [edition, setEdition] = useState(false);
    const form = useForm<{
        nom: string;
        lien: string;
        fond_sombre: boolean;
        logo: File | null;
    }>({
        nom: logo.nom,
        lien: logo.lien ?? '',
        fond_sombre: logo.fondSombre,
        logo: null,
    });
    const apercu = useApercu(form.data.logo);

    const deplacer = (direction: 'avant' | 'apres') => {
        router.patch(
            logosAdmin.ordre(logo.id).url,
            { direction },
            { preserveScroll: true },
        );
    };

    const basculer = () => {
        router.patch(
            logosAdmin.visibilite(logo.id).url,
            {},
            { preserveScroll: true },
        );
    };

    const supprimer = () => {
        if (
            !confirm(
                `Supprimer le logo « ${logo.nom} » ? Il disparaît du site public. Pour le retirer seulement un moment, masquez-le plutôt.`,
            )
        ) {
            return;
        }

        router.delete(logosAdmin.destroy(logo.id).url, {
            preserveScroll: true,
        });
    };

    const enregistrer = (event: React.FormEvent) => {
        event.preventDefault();
        // POST + _method: a file cannot travel in a real PATCH request.
        form.transform((data) => ({ ...data, _method: 'patch' }));
        form.post(logosAdmin.update(logo.id).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData('logo', null);
                setEdition(false);
            },
        });
    };

    return (
        <div className={`card logos-admin-carte${logo.visible ? '' : ' masque'}`}>
            <Tuile
                src={apercu ?? logo.logo}
                nom={logo.nom}
                sombre={edition ? form.data.fond_sombre : logo.fondSombre}
            />

            {edition ? (
                <form onSubmit={enregistrer} className="logos-admin-edition">
                    <input
                        aria-label="Nom du partenaire"
                        value={form.data.nom}
                        onChange={(event) =>
                            form.setData('nom', event.target.value)
                        }
                    />
                    <input
                        aria-label="Site web"
                        type="url"
                        placeholder="https:// (facultatif)"
                        value={form.data.lien}
                        onChange={(event) =>
                            form.setData('lien', event.target.value)
                        }
                    />
                    <label className="logos-admin-case">
                        <input
                            type="checkbox"
                            checked={form.data.fond_sombre}
                            onChange={(event) =>
                                form.setData(
                                    'fond_sombre',
                                    event.target.checked,
                                )
                            }
                        />
                        Fond foncé
                    </label>
                    <label className="logos-admin-remplacer">
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            onChange={(event) =>
                                form.setData(
                                    'logo',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                        />
                        <ImagePlus size={14} aria-hidden="true" />
                        {form.data.logo
                            ? form.data.logo.name
                            : 'Remplacer l’image'}
                    </label>
                    {(form.errors.logo ||
                        form.errors.nom ||
                        form.errors.lien) && (
                        <p className="logos-admin-erreur">
                            {form.errors.logo ??
                                form.errors.nom ??
                                form.errors.lien}
                        </p>
                    )}
                    <div className="logos-admin-boutons">
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            Enregistrer
                        </button>
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={() => {
                                form.reset();
                                form.clearErrors();
                                setEdition(false);
                            }}
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            ) : (
                <>
                    <div className="logos-admin-infos">
                        <p className="logos-admin-nom">{logo.nom}</p>
                        {!logo.visible && (
                            <span className="chip chip-gray">Masqué</span>
                        )}
                    </div>
                    {logo.lien && (
                        <a
                            href={logo.lien}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="logos-admin-lien"
                        >
                            <ExternalLink size={12} aria-hidden="true" />
                            {logo.lien.replace(/^https?:\/\//, '')}
                        </a>
                    )}

                    {(actions.modifier || actions.supprimer) && (
                        <div className="logos-admin-outils">
                            {actions.modifier && (
                                <>
                                    <button
                                        type="button"
                                        className="logos-admin-outil"
                                        onClick={() => deplacer('avant')}
                                        disabled={premier}
                                        aria-label={`Placer ${logo.nom} plus tôt`}
                                        title="Plus tôt dans la bande"
                                    >
                                        <ArrowLeft size={15} />
                                    </button>
                                    <button
                                        type="button"
                                        className="logos-admin-outil"
                                        onClick={() => deplacer('apres')}
                                        disabled={dernier}
                                        aria-label={`Placer ${logo.nom} plus tard`}
                                        title="Plus tard dans la bande"
                                    >
                                        <ArrowRight size={15} />
                                    </button>
                                    <button
                                        type="button"
                                        className="logos-admin-outil"
                                        onClick={() => setEdition(true)}
                                        aria-label={`Modifier ${logo.nom}`}
                                        title="Modifier"
                                    >
                                        <Pencil size={15} />
                                    </button>
                                    <button
                                        type="button"
                                        className="logos-admin-outil"
                                        onClick={basculer}
                                        aria-label={
                                            logo.visible
                                                ? `Masquer ${logo.nom} du site`
                                                : `Afficher ${logo.nom} sur le site`
                                        }
                                        title={
                                            logo.visible
                                                ? 'Masquer du site'
                                                : 'Afficher sur le site'
                                        }
                                    >
                                        {logo.visible ? (
                                            <EyeOff size={15} />
                                        ) : (
                                            <Eye size={15} />
                                        )}
                                    </button>
                                </>
                            )}
                            {actions.supprimer && (
                                <button
                                    type="button"
                                    className="logos-admin-outil danger"
                                    onClick={supprimer}
                                    aria-label={`Supprimer ${logo.nom}`}
                                    title="Supprimer"
                                >
                                    <Trash2 size={15} />
                                </button>
                            )}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
