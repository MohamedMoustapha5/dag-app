import {
    ArrowDown,
    ArrowUp,
    Eye,
    PencilLine,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import DocumentContrat from '@/components/espaces/document-contrat';
import { assemblerContrat, decouperContrat } from '@/lib/contrat-texte';

type ArticleEdite = { id: number; titre: string; texte: string };

let prochainId = 1;

/**
 * Edits the text of a contract article by article: the introduction, then
 * each article's title and text, which can be moved, added or removed. The
 * articles are numbered automatically. The text is handed back in the
 * stored layout ("1. Titre" lines) through onChange.
 *
 * The initial value is only read on mount: give the component a `key` to
 * reset it from a new value.
 */
export default function EditeurContrat({
    valeurInitiale,
    onChange,
    erreur,
}: {
    valeurInitiale: string | null;
    onChange: (contenu: string) => void;
    erreur?: string;
}) {
    const [initial] = useState(() => decouperContrat(valeurInitiale));
    const [preambule, setPreambule] = useState(initial.preambule);
    const [articles, setArticles] = useState<ArticleEdite[]>(() =>
        initial.articles.map((article) => ({ ...article, id: prochainId++ })),
    );
    const [apercu, setApercu] = useState(false);

    const publier = (prochainPreambule: string, prochains: ArticleEdite[]) => {
        setPreambule(prochainPreambule);
        setArticles(prochains);
        onChange(
            assemblerContrat({
                preambule: prochainPreambule,
                articles: prochains,
            }),
        );
    };

    const modifier = (id: number, champ: 'titre' | 'texte', valeur: string) =>
        publier(
            preambule,
            articles.map((article) =>
                article.id === id ? { ...article, [champ]: valeur } : article,
            ),
        );

    const deplacer = (index: number, sens: -1 | 1) => {
        const cible = index + sens;

        if (cible < 0 || cible >= articles.length) {
            return;
        }

        const prochains = [...articles];
        [prochains[index], prochains[cible]] = [
            prochains[cible],
            prochains[index],
        ];
        publier(preambule, prochains);
    };

    const supprimer = (article: ArticleEdite) => {
        if (
            (article.titre.trim() !== '' || article.texte.trim() !== '') &&
            !confirm(
                `Supprimer l'article « ${article.titre || 'sans titre'} » ?`,
            )
        ) {
            return;
        }

        publier(
            preambule,
            articles.filter((item) => item.id !== article.id),
        );
    };

    const ajouter = () =>
        publier(preambule, [
            ...articles,
            { id: prochainId++, titre: '', texte: '' },
        ]);

    return (
        <div className="editeur-contrat">
            <div className="editeur-contrat-barre" role="tablist">
                <button
                    type="button"
                    role="tab"
                    aria-selected={!apercu}
                    className={!apercu ? 'actif' : ''}
                    onClick={() => setApercu(false)}
                >
                    <PencilLine size={14} /> Modifier
                </button>
                <button
                    type="button"
                    role="tab"
                    aria-selected={apercu}
                    className={apercu ? 'actif' : ''}
                    onClick={() => setApercu(true)}
                >
                    <Eye size={14} /> Aperçu
                </button>
            </div>

            {apercu ? (
                <div className="editeur-contrat-apercu">
                    <DocumentContrat
                        contenu={assemblerContrat({ preambule, articles })}
                    />
                </div>
            ) : (
                <>
                    <div className="editeur-contrat-bloc">
                        <label htmlFor="contrat-preambule">Introduction</label>
                        <textarea
                            id="contrat-preambule"
                            rows={Math.min(8, Math.max(3, lignes(preambule)))}
                            value={preambule}
                            placeholder="Parties au contrat, référence de la demande…"
                            onChange={(event) =>
                                publier(event.target.value, articles)
                            }
                        />
                    </div>

                    <ol className="editeur-contrat-articles">
                        {articles.map((article, index) => (
                            <li
                                key={article.id}
                                className="editeur-contrat-bloc"
                            >
                                <div className="editeur-contrat-entete">
                                    <span className="editeur-contrat-numero">
                                        Article {index + 1}
                                    </span>
                                    <div className="editeur-contrat-actions">
                                        <button
                                            type="button"
                                            onClick={() => deplacer(index, -1)}
                                            disabled={index === 0}
                                            aria-label={`Monter l'article ${index + 1}`}
                                            title="Monter"
                                        >
                                            <ArrowUp size={15} />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => deplacer(index, 1)}
                                            disabled={
                                                index === articles.length - 1
                                            }
                                            aria-label={`Descendre l'article ${index + 1}`}
                                            title="Descendre"
                                        >
                                            <ArrowDown size={15} />
                                        </button>
                                        <button
                                            type="button"
                                            className="danger"
                                            onClick={() => supprimer(article)}
                                            aria-label={`Supprimer l'article ${index + 1}`}
                                            title="Supprimer"
                                        >
                                            <Trash2 size={15} />
                                        </button>
                                    </div>
                                </div>
                                <input
                                    aria-label={`Titre de l'article ${index + 1}`}
                                    placeholder="Titre de l'article (ex. Délai de prestation)"
                                    value={article.titre}
                                    maxLength={85}
                                    onChange={(event) =>
                                        modifier(
                                            article.id,
                                            'titre',
                                            event.target.value,
                                        )
                                    }
                                />
                                <textarea
                                    aria-label={`Texte de l'article ${index + 1}`}
                                    rows={Math.min(
                                        12,
                                        Math.max(3, lignes(article.texte)),
                                    )}
                                    placeholder="Texte de l'article. Une ligne vide sépare deux paragraphes ; une ligne commençant par « - » devient une puce."
                                    value={article.texte}
                                    onChange={(event) =>
                                        modifier(
                                            article.id,
                                            'texte',
                                            event.target.value,
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ol>

                    <button
                        type="button"
                        className="btn-outline editeur-contrat-ajouter"
                        onClick={ajouter}
                    >
                        <Plus size={15} /> Ajouter un article
                    </button>
                </>
            )}

            {erreur && (
                <p
                    role="alert"
                    style={{ color: 'var(--dag-red)', fontSize: 12.5 }}
                >
                    {erreur}
                </p>
            )}
        </div>
    );
}

function lignes(texte: string): number {
    return texte.split('\n').length + Math.floor(texte.length / 90);
}
