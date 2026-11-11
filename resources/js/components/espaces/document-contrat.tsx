import { decouperContrat } from '@/lib/contrat-texte';

/**
 * Renders the text of a contract as a document: preamble, then numbered
 * articles with their paragraphs, "- " lines shown as a list.
 */
export default function DocumentContrat({
    contenu,
    vide = 'Le texte de ce contrat est en cours de rédaction.',
}: {
    contenu: string | null;
    vide?: string;
}) {
    const { preambule, articles } = decouperContrat(contenu);

    if (preambule === '' && articles.length === 0) {
        return <p className="muted">{vide}</p>;
    }

    return (
        <div className="document-contrat">
            {preambule !== '' && <Paragraphes texte={preambule} />}
            {articles.map((article, index) => (
                <section key={index} className="document-contrat-article">
                    <h3>
                        <span className="document-contrat-numero">
                            Article {index + 1}
                        </span>
                        {article.titre}
                    </h3>
                    <Paragraphes texte={article.texte} />
                </section>
            ))}
        </div>
    );
}

function Paragraphes({ texte }: { texte: string }) {
    const blocs = texte
        .split(/\n\s*\n/)
        .map((bloc) => bloc.trim())
        .filter(Boolean);

    const rendu: React.ReactNode[] = [];
    let puces: string[] = [];

    const viderPuces = () => {
        if (puces.length > 0) {
            rendu.push(
                <ul key={`puces-${rendu.length}`}>
                    {puces.map((puce, index) => (
                        <li key={index}>{puce}</li>
                    ))}
                </ul>,
            );
            puces = [];
        }
    };

    for (const bloc of blocs) {
        const lignes = bloc.split('\n').map((ligne) => ligne.trim());

        if (lignes.every((ligne) => /^[-•]\s+/.test(ligne))) {
            puces.push(...lignes.map((ligne) => ligne.replace(/^[-•]\s+/, '')));

            continue;
        }

        viderPuces();
        rendu.push(<p key={`p-${rendu.length}`}>{bloc}</p>);
    }

    viderPuces();

    return <>{rendu}</>;
}
