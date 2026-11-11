import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CalendarDays,
    Loader2,
    MapPin,
    MonitorSmartphone,
    ShieldCheck,
    UserRound,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import PiecesDossier from '@/components/espaces/pieces-dossier';
import { libelleFraisInscription } from '@/lib/frais-inscription';
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
    prix: string | null;
    ouverte: boolean;
    piecesRequises: string[];
};

type Props = {
    session: Session;
};

const MODE_LABEL: Record<string, string> = {
    PRESENTIEL: 'Présentiel',
    EN_LIGNE: 'En ligne',
    LES_DEUX: 'Présentiel et en ligne',
};

const ETAPES = [
    {
        titre: "J'envoie mon dossier",
        texte: 'Les pièces demandées : ma place est réservée pendant l’examen.',
    },
    {
        titre: 'DAG valide mon dossier',
        texte: "L'équipe vérifie mes pièces et me prévient.",
    },
    {
        titre: "Je règle les frais d'inscription",
        texte: 'En ligne depuis mes factures : ma place est confirmée.',
    },
];

const MOYENS = ['Orange Money', 'MTN MoMo', 'Carte bancaire'];

function formaterDate(iso: string | null): string | null {
    if (iso === null) {
        return null;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${iso}T00:00:00`));
}

function dureeEnJours(debut: string | null, fin: string | null): number | null {
    if (debut === null || fin === null) {
        return null;
    }

    const jours =
        (new Date(`${fin}T00:00:00`).getTime() -
            new Date(`${debut}T00:00:00`).getTime()) /
            86_400_000 +
        1;

    return jours > 0 ? Math.round(jours) : null;
}

export default function InscriptionCreate({ session }: Props) {
    const form = useForm<{ pieces: (File | null)[] }>({
        pieces: session.piecesRequises.map(() => null),
    });
    const disponible =
        session.ouverte && session.placesRestantes > 0 && session.prix !== null;
    const piecesPretes = form.data.pieces.filter(Boolean).length;
    const dossierComplet = piecesPretes === session.piecesRequises.length;

    const choisirPiece = (index: number, fichier: File | null) => {
        form.setData(
            'pieces',
            form.data.pieces.map((actuel, position) =>
                position === index ? fichier : actuel,
            ),
        );
        form.clearErrors(`pieces.${index}` as 'pieces');
    };

    const envoyer = () => {
        form.post(inscriptions.store(session.id).url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const debut = formaterDate(session.dateDebut);
    const fin = formaterDate(session.dateFin);
    const duree = dureeEnJours(session.dateDebut, session.dateFin);

    const details: {
        icone: LucideIcon;
        libelle: string;
        valeur: string | null;
    }[] = [
        {
            icone: CalendarDays,
            libelle: 'Dates',
            valeur:
                debut && fin
                    ? `Du ${debut} au ${fin}${duree ? ` · ${duree} jours` : ''}`
                    : (debut ?? 'À préciser'),
        },
        {
            icone: MapPin,
            libelle: 'Lieu',
            valeur:
                [session.ville, session.lieu]
                    .filter(Boolean)
                    .filter(
                        (valeur, index, liste) =>
                            liste.indexOf(valeur) === index,
                    )
                    .join(' — ') || null,
        },
        {
            icone: MonitorSmartphone,
            libelle: 'Format',
            valeur: session.mode
                ? (MODE_LABEL[session.mode] ?? session.mode)
                : null,
        },
        {
            icone: UserRound,
            libelle: 'Formateur',
            valeur: session.formateur,
        },
        {
            icone: Users,
            libelle: 'Places restantes',
            valeur:
                session.placesRestantes > 0
                    ? `${session.placesRestantes} place${session.placesRestantes > 1 ? 's' : ''}`
                    : 'Complet',
        },
    ];

    const raisonIndisponible = !session.ouverte
        ? 'Les inscriptions à cette session ne sont pas ouvertes.'
        : session.prix === null
          ? "Les frais d'inscription de cette session ne sont pas encore publiés."
          : 'Cette session est complète.';

    return (
        <>
            <Head title="Mon inscription" />

            <Link
                href={espaceApprenant.formations()}
                className="link-action inscription-retour"
            >
                <ArrowLeft size={15} />
                Retour aux formations
            </Link>

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/formation-digital-marketing.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <span className="inscription-hero-tag">
                        {session.categorie}
                    </span>
                    <h1>{session.formation}</h1>
                    <p>
                        Vérifiez les informations de la session, joignez votre
                        dossier puis envoyez votre inscription.
                    </p>
                </div>
            </div>

            <div className="inscription-layout">
                <div>
                    <section className="card inscription-details">
                        <h2>Détails de la session</h2>
                        <ul>
                            {details
                                .filter((detail) => detail.valeur)
                                .map((detail) => (
                                    <li key={detail.libelle}>
                                        <span className="inscription-detail-icone">
                                            <detail.icone size={18} />
                                        </span>
                                        <span>
                                            <span className="inscription-detail-libelle">
                                                {detail.libelle}
                                            </span>
                                            <span className="inscription-detail-valeur">
                                                {detail.valeur}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                        </ul>
                    </section>

                    <section className="card inscription-dossier">
                        <h2>Mon dossier d'inscription</h2>
                        <p>
                            Joignez une copie lisible de chaque pièce (scan ou
                            photo). L'équipe DAG vérifie votre dossier avant de
                            vous ouvrir le paiement des frais d'inscription.
                        </p>
                        <PiecesDossier
                            pieces={session.piecesRequises}
                            fichiers={form.data.pieces}
                            onChange={choisirPiece}
                            erreurs={form.errors as Record<string, string>}
                        />
                    </section>
                </div>

                <aside className="card inscription-recap">
                    <p className="inscription-recap-libelle">
                        Frais d'inscription
                    </p>
                    <p className="inscription-recap-montant">
                        {libelleFraisInscription(session.prix)}
                    </p>
                    <div className="inscription-moyens">
                        {MOYENS.map((moyen) => (
                            <span key={moyen}>{moyen}</span>
                        ))}
                    </div>

                    {!disponible && (
                        <p role="alert" className="inscription-alerte">
                            <AlertCircle size={16} />
                            {raisonIndisponible}
                        </p>
                    )}

                    {disponible && !dossierComplet && (
                        <p className="muted" style={{ fontSize: 12.5 }}>
                            Pièces jointes : {piecesPretes}/
                            {session.piecesRequises.length}. Joignez toutes les
                            pièces pour envoyer votre dossier.
                        </p>
                    )}

                    <button
                        type="button"
                        className="btn-primary btn-block"
                        disabled={
                            !disponible || !dossierComplet || form.processing
                        }
                        onClick={envoyer}
                    >
                        {form.processing ? (
                            <>
                                <Loader2 size={16} className="animate-spin" />
                                Envoi du dossier…
                            </>
                        ) : (
                            'Envoyer mon dossier'
                        )}
                    </button>
                    <p className="inscription-securite">
                        <ShieldCheck size={14} />
                        Aucun paiement maintenant : vous réglez les frais une
                        fois votre dossier validé par DAG. Vos documents restent
                        confidentiels.
                    </p>

                    <ol className="inscription-etapes">
                        {ETAPES.map((etape, index) => (
                            <li key={etape.titre}>
                                <span className="inscription-etape-numero">
                                    {index + 1}
                                </span>
                                <span>
                                    <strong>{etape.titre}</strong>
                                    <span>{etape.texte}</span>
                                </span>
                            </li>
                        ))}
                    </ol>

                    <Link
                        href={espaceApprenant.inscriptions()}
                        className="link-action"
                        style={{ fontSize: 13 }}
                    >
                        Voir mes inscriptions →
                    </Link>
                </aside>
            </div>
        </>
    );
}
