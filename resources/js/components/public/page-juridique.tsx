import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import PublicLayout from '@/layouts/public-layout';

export type SectionJuridique = {
    id: string;
    titre: string;
    contenu: ReactNode;
};

type Props = {
    titre: string;
    badge: string;
    introduction: string;
    miseAJour: string;
    sections: SectionJuridique[];
};

/**
 * Shared layout of DAG's legal pages (CGU, privacy policy): a hero, a
 * sticky table of contents on wide screens and numbered sections.
 */
export default function PageJuridique({
    titre,
    badge,
    introduction,
    miseAJour,
    sections,
}: Props) {
    return (
        <PublicLayout>
            <Head title={`${titre} — DAG`} />

            <section className="contact-hero-wrap reveal container">
                <div
                    className="contact-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/office-glass-dubai.jpg')",
                    }}
                >
                    <div className="contact-hero-overlay" />
                    <div className="contact-hero-content">
                        <span className="badge">{badge}</span>
                        <h1>{titre}</h1>
                        <p>{introduction}</p>
                    </div>
                </div>
            </section>

            <section className="section-gap container">
                <div className="juridique-layout">
                    <nav
                        className="juridique-sommaire card"
                        aria-label="Sommaire"
                    >
                        <p className="juridique-sommaire-titre">Sommaire</p>
                        <ol>
                            {sections.map((section, index) => (
                                <li key={section.id}>
                                    <a href={`#${section.id}`}>
                                        <span>{index + 1}.</span>
                                        {section.titre}
                                    </a>
                                </li>
                            ))}
                        </ol>
                    </nav>

                    <article className="juridique-contenu card">
                        <p className="juridique-maj">
                            Dernière mise à jour : {miseAJour}
                        </p>
                        {sections.map((section, index) => (
                            <section
                                key={section.id}
                                id={section.id}
                                className="juridique-section"
                            >
                                <h2>
                                    <span>{index + 1}.</span>
                                    {section.titre}
                                </h2>
                                {section.contenu}
                            </section>
                        ))}
                    </article>
                </div>
            </section>
        </PublicLayout>
    );
}
