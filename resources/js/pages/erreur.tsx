import { Head } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    statut: 403 | 404 | 419 | 429 | 500 | 503;
};

/**
 * Written in French like every page; the interface translator shows it in
 * English when the visitor chose English.
 */
const MESSAGES: Record<Props['statut'], { titre: string; texte: string }> = {
    403: {
        titre: 'Accès refusé',
        texte: "Vous n'avez pas l'autorisation d'ouvrir cette page.",
    },
    404: {
        titre: 'Page introuvable',
        texte: "La page demandée n'existe pas ou a été déplacée.",
    },
    419: {
        titre: 'Session expirée',
        texte: 'Cette page est restée ouverte trop longtemps. Rechargez-la puis recommencez.',
    },
    429: {
        titre: 'Trop de tentatives',
        texte: 'Vous avez fait trop de demandes en peu de temps. Patientez une minute puis réessayez.',
    },
    500: {
        titre: 'Erreur du serveur',
        texte: 'Un problème est survenu de notre côté. Réessayez dans quelques instants.',
    },
    503: {
        titre: 'Service indisponible',
        texte: 'Le site est en maintenance. Revenez dans quelques minutes.',
    },
};

export default function Erreur({ statut }: Props) {
    const { titre, texte } = MESSAGES[statut] ?? MESSAGES[500];
    const recharger = statut === 419 || statut === 500 || statut === 503;

    return (
        <AuthLayout title={titre} description={texte}>
            <Head title={titre} />

            <p
                translate="no"
                style={{
                    textAlign: 'center',
                    fontSize: 44,
                    fontWeight: 800,
                    lineHeight: 1,
                    marginBottom: 24,
                    color: 'var(--dag-navy)',
                }}
            >
                {statut}
            </p>

            <button
                type="button"
                className="btn-primary btn-block"
                onClick={() =>
                    recharger ? window.location.reload() : window.history.back()
                }
            >
                {recharger ? 'Recharger la page' : 'Page précédente'}
            </button>
        </AuthLayout>
    );
}
