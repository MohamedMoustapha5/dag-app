import { Head, Link, useForm } from '@inertiajs/react';
import {
    Award,
    BadgeCheck,
    CalendarRange,
    Check,
    Download,
    FileSignature,
    Handshake,
    Sparkles,
    UserRound,
} from 'lucide-react';
import SuiviProcedure, {
    type Parcours,
} from '@/components/espaces/suivi-procedure';
import contratsRoutes from '@/routes/espace-client/contrats';
import partenariatRoutes from '@/routes/espace-client/partenariat';

type Domaine = { code: string; libelle: string };

type Partenariat = {
    id: string;
    reference: string;
    origine: 'DEMANDE' | 'PROPOSITION';
    etape: string;
    parcours: Parcours;
    domaines: string[];
    message: string | null;
    duree: string | null;
    debut: string | null;
    fin: string | null;
    motifRefus: string | null;
    creeLe: string | null;
    contrat: { id: string; reference: string; estSigne: boolean } | null;
};

type Props = {
    domaines: Domaine[];
    partenariat: Partenariat | null;
    peutDemander: boolean;
    historique: {
        id: string;
        reference: string;
        etape: string;
        creeLe: string | null;
    }[];
};

const AVANTAGES = [
    {
        icone: Award,
        titre: 'Statut officiel',
        texte: 'Votre structure devient « Partenaire officiel de Digital Agency Group », un gage de confiance auprès de vos clients et des talents.',
    },
    {
        icone: UserRound,
        titre: 'Interlocuteur dédié',
        texte: 'Un contact unique chez DAG suit vos besoins et traite vos demandes en priorité.',
    },
    {
        icone: Sparkles,
        titre: 'Accès en priorité',
        texte: 'Vous êtes informé en premier de nos programmes, de nos sessions de formation et des profils que nous formons.',
    },
];

const ETAPE_LIBELLE: Record<string, string> = {
    EN_ETUDE: 'En cours d’étude',
    A_SIGNER: 'Contrat à signer',
    EN_REDACTION: 'Contrat en révision',
    A_VALIDER: 'En validation',
    ACTIF: 'Actif',
    TERMINE: 'Terminé',
    REFUSE: 'Non retenue',
    ANNULE: 'Annulé',
};

function dateLongue(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function PartenariatPage({
    domaines,
    partenariat,
    peutDemander,
    historique,
}: Props) {
    const libelleDomaine = (code: string) =>
        domaines.find((domaine) => domaine.code === code)?.libelle ?? code;

    return (
        <>
            <Head title="Partenariat DAG" />

            <div className="partenariat-entete">
                <span className="partenariat-entete-icone" aria-hidden="true">
                    <Handshake size={22} />
                </span>
                <div>
                    <h1>Partenariat Digital Agency Group</h1>
                    <p className="muted">
                        Un cadre contractuel pour collaborer durablement avec
                        DAG : talents, formation, projets digitaux et visibilité
                        commune.
                    </p>
                </div>
            </div>

            {partenariat?.etape === 'ACTIF' && (
                <PartenaireOfficiel
                    partenariat={partenariat}
                    libelleDomaine={libelleDomaine}
                />
            )}

            {partenariat && partenariat.etape !== 'ACTIF' && (
                <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                    <div className="partenariat-suivi-tete">
                        <div>
                            <p className="partenariat-suivi-titre">
                                {partenariat.origine === 'PROPOSITION'
                                    ? 'Proposition de partenariat de DAG'
                                    : 'Votre demande de partenariat'}
                            </p>
                            <p className="muted" style={{ fontSize: 13 }}>
                                {partenariat.reference}
                                {partenariat.creeLe &&
                                    ` · ${dateLongue(partenariat.creeLe)}`}
                                {partenariat.duree && ` · ${partenariat.duree}`}
                            </p>
                        </div>
                        <span className="chip chip-blue">
                            {ETAPE_LIBELLE[partenariat.etape] ??
                                partenariat.etape}
                        </span>
                    </div>

                    <SuiviProcedure
                        parcours={partenariat.parcours}
                        messageTermine="Partenariat terminé. Merci pour cette collaboration !"
                    >
                        {partenariat.parcours.prochaine?.action ===
                            'signer_contrat' &&
                            partenariat.contrat && (
                                <Link
                                    href={contratsRoutes.show(
                                        partenariat.contrat.id,
                                    )}
                                    className="btn-primary"
                                >
                                    <FileSignature size={16} /> Lire et signer
                                    le contrat
                                </Link>
                            )}
                    </SuiviProcedure>

                    {partenariat.origine === 'PROPOSITION' &&
                        partenariat.message && (
                            <blockquote className="partenariat-mot-dag">
                                <p className="partenariat-mot-dag-titre">
                                    Le mot de l’équipe DAG
                                </p>
                                {partenariat.message}
                            </blockquote>
                        )}

                    {partenariat.domaines.length > 0 && (
                        <div style={{ marginTop: 18 }}>
                            <p className="partenariat-libelle">
                                Domaines de collaboration
                            </p>
                            <ul className="partenariat-domaines-liste">
                                {partenariat.domaines.map((code) => (
                                    <li key={code}>
                                        <Check size={14} aria-hidden="true" />
                                        {libelleDomaine(code)}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}

            {peutDemander && (
                <>
                    {partenariat === null && (
                        <div className="partenariat-avantages">
                            {AVANTAGES.map((avantage) => (
                                <div
                                    key={avantage.titre}
                                    className="card partenariat-avantage"
                                >
                                    <span
                                        className="partenariat-avantage-icone"
                                        aria-hidden="true"
                                    >
                                        <avantage.icone size={20} />
                                    </span>
                                    <p className="partenariat-avantage-titre">
                                        {avantage.titre}
                                    </p>
                                    <p
                                        className="muted"
                                        style={{ fontSize: 13 }}
                                    >
                                        {avantage.texte}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                    <FormulaireDemande
                        domaines={domaines}
                        nouvelle={partenariat !== null}
                    />
                </>
            )}

            {historique.length > 0 && (
                <div className="card" style={{ padding: 24, marginTop: 24 }}>
                    <p className="partenariat-libelle">
                        Partenariats précédents
                    </p>
                    <ul className="partenariat-historique">
                        {historique.map((ligne) => (
                            <li key={ligne.id}>
                                <span>
                                    {ligne.reference}
                                    {ligne.creeLe &&
                                        ` · ${dateLongue(ligne.creeLe)}`}
                                </span>
                                <span className="chip chip-gray">
                                    {ETAPE_LIBELLE[ligne.etape] ?? ligne.etape}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </>
    );
}

function PartenaireOfficiel({
    partenariat,
    libelleDomaine,
}: {
    partenariat: Partenariat;
    libelleDomaine: (code: string) => string;
}) {
    return (
        <div className="card partenariat-officiel">
            <div className="partenariat-officiel-sceau" aria-hidden="true">
                <BadgeCheck size={34} />
            </div>
            <div className="partenariat-officiel-corps">
                <p className="partenariat-officiel-surtitre">
                    Partenaire officiel
                </p>
                <p className="partenariat-officiel-titre">
                    Digital Agency Group
                </p>
                {partenariat.debut && (
                    <p className="partenariat-officiel-periode">
                        <CalendarRange size={15} aria-hidden="true" />
                        {partenariat.fin
                            ? `Du ${dateLongue(partenariat.debut)} au ${dateLongue(partenariat.fin)}`
                            : `Depuis le ${dateLongue(partenariat.debut)} · durée indéterminée`}
                    </p>
                )}
                {partenariat.domaines.length > 0 && (
                    <ul className="partenariat-domaines-liste">
                        {partenariat.domaines.map((code) => (
                            <li key={code}>
                                <Check size={14} aria-hidden="true" />
                                {libelleDomaine(code)}
                            </li>
                        ))}
                    </ul>
                )}
                {partenariat.contrat && (
                    <div className="partenariat-officiel-actions">
                        <Link
                            href={contratsRoutes.show(partenariat.contrat.id)}
                            className="btn-outline"
                        >
                            <FileSignature size={15} /> Voir le contrat
                        </Link>
                        <a
                            href={
                                contratsRoutes.pdf(partenariat.contrat.id).url
                            }
                            className="btn-outline"
                        >
                            <Download size={15} /> Télécharger le contrat signé
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}

function FormulaireDemande({
    domaines,
    nouvelle,
}: {
    domaines: Domaine[];
    nouvelle: boolean;
}) {
    const form = useForm<{ domaines: string[]; message: string }>({
        domaines: [],
        message: '',
    });

    const basculer = (code: string) => {
        form.setData(
            'domaines',
            form.data.domaines.includes(code)
                ? form.data.domaines.filter((domaine) => domaine !== code)
                : [...form.data.domaines, code],
        );
    };

    const envoyer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(partenariatRoutes.demander().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={envoyer} className="card" style={{ padding: 24 }}>
            <p className="partenariat-suivi-titre">
                {nouvelle
                    ? 'Présenter une nouvelle demande'
                    : 'Demander un partenariat'}
            </p>
            <p className="muted" style={{ fontSize: 13, marginBottom: 18 }}>
                Votre demande est étudiée par l’équipe DAG. Si elle est
                acceptée, le contrat de partenariat est rédigé et vous est
                envoyé ici pour signature.
            </p>

            <fieldset className="partenariat-fieldset">
                <legend className="partenariat-libelle">
                    Domaines de collaboration envisagés
                </legend>
                <div className="partenariat-domaines">
                    {domaines.map((domaine) => {
                        const choisi = form.data.domaines.includes(
                            domaine.code,
                        );

                        return (
                            <label
                                key={domaine.code}
                                className={`partenariat-domaine${choisi ? ' choisi' : ''}`}
                            >
                                <input
                                    type="checkbox"
                                    checked={choisi}
                                    onChange={() => basculer(domaine.code)}
                                />
                                <span
                                    className="partenariat-domaine-case"
                                    aria-hidden="true"
                                >
                                    {choisi && <Check size={13} />}
                                </span>
                                {domaine.libelle}
                            </label>
                        );
                    })}
                </div>
                {form.errors.domaines && (
                    <p className="partenariat-erreur">{form.errors.domaines}</p>
                )}
            </fieldset>

            <label htmlFor="message" className="partenariat-libelle">
                Présentez votre structure et votre projet
            </label>
            <textarea
                id="message"
                rows={5}
                value={form.data.message}
                onChange={(event) =>
                    form.setData('message', event.target.value)
                }
                placeholder="Qui êtes-vous, que souhaitez-vous construire avec DAG, et quels sont vos besoins (recrutements, formations, projets) ?"
            />
            {form.errors.message && (
                <p className="partenariat-erreur">{form.errors.message}</p>
            )}

            <button
                type="submit"
                className="btn-primary"
                disabled={form.processing}
                style={{ marginTop: 16 }}
            >
                <Handshake size={16} />
                {form.processing ? 'Envoi…' : 'Envoyer ma demande'}
            </button>
        </form>
    );
}
