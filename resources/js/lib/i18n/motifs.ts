/**
 * Texts that contain a variable part (a reference, a number, a name): they
 * cannot be listed one by one, so each pattern rewrites the fixed part and
 * keeps the variable one.
 */
export type Remplacement = (
    correspondance: RegExpMatchArray,
    traduire: (texte: string) => string,
) => string;

const pluriel = (nombre: string, mot: string): string =>
    Number(nombre) > 1 ? `${mot}s` : mot;

export const motifs: [RegExp, Remplacement][] = [
    // Numbered steps ("1. Choisissez votre formation")
    [/^(\d+)\. (.+)$/, (m, t) => `${m[1]}. ${t(m[2])}`],

    // Page title: "<page> - <application name>"
    [/^(.+) - (DAG — Digital Agency Group)$/, (m, t) => `${t(m[1])} - ${m[2]}`],

    // "Novembre 2026" in the session filters
    [
        /^(Janvier|Février|Mars|Avril|Mai|Juin|Juillet|Août|Septembre|Octobre|Novembre|Décembre) (\d{4})$/,
        (m) => {
            const mois: Record<string, string> = {
                Janvier: 'January',
                Février: 'February',
                Mars: 'March',
                Avril: 'April',
                Mai: 'May',
                Juin: 'June',
                Juillet: 'July',
                Août: 'August',
                Septembre: 'September',
                Octobre: 'October',
                Novembre: 'November',
                Décembre: 'December',
            };

            return `${mois[m[1]]} ${m[2]}`;
        },
    ],

    // Prices and durations
    [/^(.+) \/ mois$/, (m) => `${m[1]} / month`],
    [/^(.+) \/ projet$/, (m) => `${m[1]} / project`],
    [/^(.+) \/ page$/, (m) => `${m[1]} / page`],
    [/^À partir de (.+)$/, (m) => `From ${m[1]}`],
    [/^(\d+) ?mois$/, (m) => `${m[1]} months`],
    [/^(\d+) ?semaines?$/, (m) => `${m[1]} ${pluriel(m[1], 'week')}`],
    [/^(\d+) ?heures?$/, (m) => `${m[1]} ${pluriel(m[1], 'hour')}`],

    // Relative times and counters
    [/^Il y a (\d+) min$/, (m) => `${m[1]} min ago`],
    [/^Il y a (\d+) h$/, (m) => `${m[1]} h ago`],
    [/^Il y a (\d+) jours?$/, (m) => `${m[1]} ${pluriel(m[1], 'day')} ago`],
    [
        /^Notifications : (\d+) non lues?$/,
        (m) => `Notifications: ${m[1]} unread`,
    ],
    [/^Aujourd'hui, (.+)$/, (m) => `Today, ${m[1]}`],
    [/^Hier, (.+)$/, (m) => `Yesterday, ${m[1]}`],
    [/^Terminé le (.+)$/, (m) => `Ended on ${m[1]}`],
    [
        /^Expiré depuis (\d+) jours?$/,
        (m) => `Expired ${m[1]} ${pluriel(m[1], 'day')} ago`,
    ],
    [
        /^(\d+) jours? restants?$/,
        (m) => `${m[1]} ${pluriel(m[1], 'day')} left`,
    ],
    [/^expiré depuis (\d+) j$/, (m) => `expired ${m[1]} d ago`],
    [/^dans (\d+) j$/, (m) => `in ${m[1]} d`],
    [/^Attestation validée le (.+)$/, (m) => `Certificate validated on ${m[1]}`],
    [/^Date limite : (.+) ·$/, (m) => `Deadline: ${m[1]} ·`],
    [/^· limite (.+)$/, (m) => `· deadline ${m[1]}`],
    [/^le (\d{1,2}[/ ].+)$/, (m) => `on ${m[1]}`],
    [/^Payer (.+) FCFA$/, (m) => `Pay ${m[1]} FCFA`],
    [/^Numéro (.+)$/, (m) => `Number ${m[1]}`],
    [/^(\d+) séances?$/, (m) => `${m[1]} ${pluriel(m[1], 'sitting')}`],
    [/^(\d+) événements?$/, (m) => `${m[1]} ${pluriel(m[1], 'event')}`],
    [
        /^(\d+) séance\(s\) restante\(s\)$/,
        (m) => `${m[1]} session(s) left`,
    ],
    [/^(\d+) places?$/, (m) => `${m[1]} ${pluriel(m[1], 'place')}`],

    // Page titles and headings carrying a name or reference
    [/^(.+) — Formations DAG$/, (m) => `${m[1]} — DAG Training`],
    [/^(.+) — Administration$/, (m, t) => `${t(m[1])} — Administration`],
    [/^Contrat (.+)$/, (m) => `Contract ${m[1]}`],
    [/^Demande (.+)$/, (m) => `Request ${m[1]}`],
    [/^Facture (.+)$/, (m) => `Invoice ${m[1]}`],
    [/^Dossier de (.+)$/, (m) => `File of ${m[1]}`],
    [/^Progression — (.+)$/, (m) => `Progress — ${m[1]}`],
    [/^Offre pour (.+)$/, (m) => `Offer for ${m[1]}`],
    [/^Formule (.+)$/, (m) => `Plan ${m[1]}`],
    [/^Témoignage (\d+)$/, (m) => `Testimonial ${m[1]}`],

    // Notifications written by the server
    [/^Votre devis est prêt — (.+)$/, (m) => `Your quote is ready — ${m[1]}`],
    [/^Paiement reçu — (.+)$/, (m) => `Payment received — ${m[1]}`],
    [/^Contrat signé — (.+)$/, (m) => `Contract signed — ${m[1]}`],
    [
        /^Votre contrat est prêt à signer — (.+)$/,
        (m) => `Your contract is ready to sign — ${m[1]}`,
    ],
    [
        /^Rappel : votre contrat attend votre signature — (.+)$/,
        (m) => `Reminder: your contract is waiting for your signature — ${m[1]}`,
    ],
    [/^Nouvelle demande — (.+)$/, (m) => `New request — ${m[1]}`],
    [
        /^Renouvellement d'abonnement — (.+)$/,
        (m) => `Subscription renewal — ${m[1]}`,
    ],
    [/^Nouvelle inscription — (.+)$/, (m) => `New enrolment — ${m[1]}`],
    [
        /^Rappel : séance demain — (.+)$/,
        (m) => `Reminder: session tomorrow — ${m[1]}`,
    ],
    [
        /^Notre équipe a préparé le devis de votre demande « (.+) » : (.+) FCFA\.$/,
        (m) => `Our team has prepared the quote for your request “${m[1]}”: ${m[2]} FCFA.`,
    ],
    [
        /^Nous avons bien reçu votre paiement de (.+) FCFA pour « (.+) »\.$/,
        (m) => `We have received your payment of ${m[1]} FCFA for “${m[2]}”.`,
    ],
    [
        /^Le contrat « (.+) » est disponible dans votre espace\. Sa signature est nécessaire pour que la prestation démarre\.$/,
        (m) => `The contract “${m[1]}” is available in your area. Its signature is required for the service to start.`,
    ],
    [
        /^Merci : votre contrat « (.+) » a bien été signé\. La prestation peut démarrer\.$/,
        (m) => `Thank you: your contract “${m[1]}” has been signed. The service can start.`,
    ],
    [
        /^(.+) a signé le contrat « (.+) »\. La prestation peut démarrer\.$/,
        (m) => `${m[1]} signed the contract “${m[2]}”. The service can start.`,
    ],
    [/^(.+) a demandé : (.+)\.$/, (m) => `${m[1]} requested: ${m[2]}.`],
    [
        /^(.+) souhaite renouveler la formule (.+)\.$/,
        (m) => `${m[1]} wishes to renew the ${m[2]} plan.`,
    ],
    [
        /^(.+) demande à s'inscrire à la session de (.+)\. Elle attend la validation du paiement\.$/,
        (m) => `${m[1]} wants to enrol in the ${m[2]} session. It is awaiting payment validation.`,
    ],
    [
        /^(.+) FCFA reçus pour (.+)\.$/,
        (m) => `${m[1]} FCFA received for ${m[2]}.`,
    ],
    [
        /^Félicitations : votre attestation pour la formation « (.+) » a été validée et peut être téléchargée\.$/,
        (m) => `Congratulations: your certificate for the training “${m[1]}” has been validated and can be downloaded.`,
    ],
    [
        /^DAG vous transmet un candidat pour votre offre « (.+) »\. Consultez son dossier, puis sélectionnez-le ou refusez-le\.$/,
        (m) => `DAG sends you a candidate for your offer “${m[1]}”. Review their file, then select or decline them.`,
    ],

    // Confirmation messages and toasts written by the server
    [/^Contrat (.+) créé\.$/, (m) => `Contract ${m[1]} created.`],
    [/^Facture (.+) créée\.$/, (m) => `Invoice ${m[1]} created.`],
    [/^Devis envoyé pour (.+)\.$/, (m) => `Quote sent for ${m[1]}.`],
    [
        /^Attestation validée pour (.+) — (.+)\.$/,
        (m) => `Certificate validated for ${m[1]} — ${m[2]}.`,
    ],
    [
        /^Candidature déposée pour (.+) sur « (.+) »\.$/,
        (m) => `Application submitted for ${m[1]} on “${m[2]}”.`,
    ],
    [/^Compte créé pour (.+)$/, (m) => `Account created for ${m[1]}`],
    [
        /^Droits de « (.+) » réinitialisés\.$/,
        (m) => `Rights of “${m[1]}” reset.`,
    ],

    // Confirmation dialogs
    [
        /^Supprimer la formule « (.+) » \?$/,
        (m) => `Delete the plan “${m[1]}”?`,
    ],
    [
        /^Renouveler l'abonnement « (.+) » de (.+) \? Un nouveau cycle démarre\.$/,
        (m) => `Renew the “${m[1]}” subscription of ${m[2]}? A new cycle starts.`,
    ],
    [
        /^Supprimer la session « (.+) » \? Ses séances seront également supprimées\.$/,
        (m) => `Delete the session “${m[1]}”? Its sittings will also be deleted.`,
    ],
    [/^Supprimer « (.+) » \?$/, (m) => `Delete “${m[1]}”?`],
    [
        /^Supprimer « (.+) » du catalogue \?$/,
        (m) => `Delete “${m[1]}” from the catalogue?`,
    ],
    [
        /^Désactiver le compte de (.+) \?$/,
        (m) => `Deactivate the account of ${m[1]}?`,
    ],
    [
        /^Remettre les droits de « (.+) » à leurs valeurs par défaut \?$/,
        (m) => `Reset the rights of “${m[1]}” to their default values?`,
    ],
    [
        /^Supprimer le dossier de « (.+) » \? .*$/,
        (m) => `Delete the file of “${m[1]}”? Their CV and, if it was turned into a learner file, that file will be permanently deleted.`,
    ],
    [
        /^Supprimer le compte de « (.+) » \? Son dossier.*$/,
        (m) => `Delete the account of “${m[1]}”? Their file, enrolments, applications and certificates will be permanently deleted. Their existing invoices and contracts are kept.`,
    ],
    [
        /^Supprimer le compte de « (.+) » \? Cette action est définitive\..*$/,
        (m) => `Delete the account of “${m[1]}”? This action is permanent. Their existing requests, contracts and invoices are kept but will no longer be linked to a client account.`,
    ],
];
