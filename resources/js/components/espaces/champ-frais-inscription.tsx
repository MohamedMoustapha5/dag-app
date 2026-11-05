type Props = {
    id: string;
    valeur: string;
    erreur?: string;
    onChange: (valeur: string) => void;
};

/**
 * Registration fee field of the admin session forms: the amount the
 * apprenant is billed when enrolling, required before opening the session.
 */
export default function ChampFraisInscription({
    id,
    valeur,
    erreur,
    onChange,
}: Props) {
    return (
        <div>
            <label htmlFor={id}>Frais d'inscription (FCFA)</label>
            <input
                id={id}
                type="number"
                min={1}
                step={1}
                inputMode="numeric"
                placeholder="Ex. 75000"
                value={valeur}
                onChange={(event) => onChange(event.target.value)}
            />
            <p
                className="muted"
                style={{ fontSize: 12, marginTop: 4, lineHeight: 1.5 }}
            >
                Montant facturé à l'apprenant à son inscription. Obligatoire
                pour ouvrir la session aux inscriptions.
            </p>
            {erreur && (
                <p
                    style={{
                        color: 'var(--dag-red)',
                        fontSize: 12,
                        marginTop: 4,
                    }}
                >
                    {erreur}
                </p>
            )}
        </div>
    );
}
