import { Head, useForm } from '@inertiajs/react';
import { FileCheck, Upload } from 'lucide-react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import dossierRoutes from '@/routes/espace-apprenant/dossier';

type Props = {
    apprenant: EspaceApprenantInfo;
    dossier: {
        ville: string | null;
        telephone: string | null;
        dateNaissance: string | null;
        cvNomFichier: string | null;
        consentementCv: boolean;
    };
};

export default function ApprenantDossier({ apprenant, dossier }: Props) {
    const { data, setData, patch, processing, errors, recentlySuccessful } =
        useForm({
            ville: dossier.ville ?? '',
            telephone: dossier.telephone ?? '',
            date_naissance: dossier.dateNaissance ?? '',
            cv: null as File | null,
            consentement_cv: dossier.consentementCv,
        });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        patch(dossierRoutes.update().url);
    };

    return (
        <>
            <Head title="Mon dossier" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/man-laptop-smile.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Mon dossier</h1>
                    <p>
                        Vos informations personnelles et votre CV, utilisés pour
                        vos candidatures.
                    </p>
                </div>
            </div>

            <div className="grid-3">
                <div
                    className="card"
                    style={{ padding: 28, gridColumn: 'span 2' }}
                >
                    <h2 style={{ fontSize: 15, marginBottom: 20 }}>
                        Informations personnelles
                    </h2>
                    <form
                        onSubmit={submit}
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 16,
                        }}
                    >
                        <div className="form-row-2">
                            <div>
                                <label htmlFor="ville">Ville</label>
                                <input
                                    id="ville"
                                    type="text"
                                    value={data.ville}
                                    onChange={(event) =>
                                        setData('ville', event.target.value)
                                    }
                                    placeholder="Douala"
                                />
                                {errors.ville && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.ville}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label htmlFor="telephone">Téléphone</label>
                                <input
                                    id="telephone"
                                    type="text"
                                    value={data.telephone}
                                    onChange={(event) =>
                                        setData('telephone', event.target.value)
                                    }
                                    placeholder="+237 6 55 00 11 22"
                                />
                                {errors.telephone && (
                                    <p
                                        style={{
                                            color: 'var(--dag-red)',
                                            fontSize: 12,
                                            marginTop: 4,
                                        }}
                                    >
                                        {errors.telephone}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="date_naissance">
                                Date de naissance
                            </label>
                            <input
                                id="date_naissance"
                                type="date"
                                value={data.date_naissance}
                                onChange={(event) =>
                                    setData(
                                        'date_naissance',
                                        event.target.value,
                                    )
                                }
                            />
                            {errors.date_naissance && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.date_naissance}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="cv">
                                CV (PDF, Word — 5 Mo max)
                            </label>
                            {dossier.cvNomFichier && (
                                <p
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 6,
                                        fontSize: 12.5,
                                        color: 'var(--dag-green)',
                                        marginBottom: 8,
                                    }}
                                >
                                    <FileCheck size={14} />
                                    CV actuel : {dossier.cvNomFichier}
                                </p>
                            )}
                            <input
                                id="cv"
                                type="file"
                                accept=".pdf,.doc,.docx"
                                onChange={(event) =>
                                    setData(
                                        'cv',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {errors.cv && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {errors.cv}
                                </p>
                            )}
                        </div>

                        <label
                            htmlFor="consentement_cv"
                            style={{
                                display: 'flex',
                                alignItems: 'flex-start',
                                gap: 10,
                                fontSize: 13,
                                lineHeight: 1.6,
                                color: 'var(--dag-text)',
                                cursor: 'pointer',
                            }}
                        >
                            <input
                                type="checkbox"
                                id="consentement_cv"
                                checked={data.consentement_cv}
                                onChange={(event) =>
                                    setData(
                                        'consentement_cv',
                                        event.target.checked,
                                    )
                                }
                                style={{ marginTop: 3 }}
                            />
                            <span>
                                J'autorise DAG à transmettre mon CV aux
                                entreprises partenaires pour des opportunités de
                                stage ou d'emploi.
                            </span>
                        </label>

                        <button
                            type="submit"
                            className="btn-primary"
                            style={{ alignSelf: 'flex-start' }}
                            disabled={processing}
                        >
                            <Upload
                                size={15}
                                style={{ marginRight: 6, verticalAlign: -2 }}
                            />
                            {processing
                                ? 'Enregistrement…'
                                : 'Enregistrer mon dossier'}
                        </button>
                        {recentlySuccessful && (
                            <p
                                style={{
                                    fontSize: 12.5,
                                    color: 'var(--dag-green)',
                                }}
                            >
                                Dossier mis à jour.
                            </p>
                        )}
                    </form>
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p style={{ fontSize: 14, fontWeight: 700 }}>
                        {apprenant.nom}
                    </p>
                    <p
                        className="muted"
                        style={{ fontSize: 12.5, marginTop: 4 }}
                    >
                        {apprenant.typeLabel}
                    </p>
                    <p
                        className="muted"
                        style={{
                            fontSize: 12.5,
                            marginTop: 16,
                            lineHeight: 1.6,
                        }}
                    >
                        Un dossier complet (CV à jour, coordonnées exactes)
                        augmente vos chances d'être retenu par nos entreprises
                        partenaires.
                    </p>
                </div>
            </div>
        </>
    );
}
