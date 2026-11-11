import { CheckCircle2, FileText, Upload } from 'lucide-react';

type Props = {
    pieces: string[];
    fichiers: (File | null)[];
    onChange: (index: number, fichier: File | null) => void;
    erreurs: Record<string, string | undefined>;
    /** Names of the files already joined to the dossier, by piece label. */
    deposees?: Record<string, string>;
};

/**
 * One row per document of an enrolment dossier (CNI, birth certificate…):
 * the apprenant picks a PDF or a photo for each, and sees which ones are
 * ready. The files are sent as pieces[<index>].
 */
export default function PiecesDossier({
    pieces,
    fichiers,
    onChange,
    erreurs,
    deposees = {},
}: Props) {
    return (
        <ul className="pieces-dossier">
            {pieces.map((libelle, index) => {
                const fichier = fichiers[index] ?? null;
                const dejaDeposee = deposees[libelle];
                const erreur = erreurs[`pieces.${index}`];
                const id = `piece-${index}`;

                return (
                    <li
                        key={libelle}
                        className={`pieces-dossier-ligne${fichier || dejaDeposee ? ' prete' : ''}${erreur ? ' en-erreur' : ''}`}
                    >
                        <span className="pieces-dossier-icone">
                            {fichier || dejaDeposee ? (
                                <CheckCircle2 size={18} />
                            ) : (
                                <FileText size={18} />
                            )}
                        </span>
                        <span className="pieces-dossier-texte">
                            <span className="pieces-dossier-libelle">
                                {libelle}
                            </span>
                            <span className="pieces-dossier-fichier">
                                {fichier
                                    ? fichier.name
                                    : dejaDeposee
                                      ? `Déjà déposée : ${dejaDeposee}`
                                      : 'PDF, JPG ou PNG — 5 Mo maximum'}
                            </span>
                            {erreur && (
                                <span
                                    role="alert"
                                    className="pieces-dossier-erreur"
                                >
                                    {erreur}
                                </span>
                            )}
                        </span>
                        <label htmlFor={id} className="pieces-dossier-bouton">
                            <Upload size={14} />
                            {fichier || dejaDeposee ? 'Remplacer' : 'Choisir'}
                        </label>
                        <input
                            id={id}
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                            className="sr-only"
                            onChange={(event) =>
                                onChange(index, event.target.files?.[0] ?? null)
                            }
                        />
                    </li>
                );
            })}
        </ul>
    );
}
