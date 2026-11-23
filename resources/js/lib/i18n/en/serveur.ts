/** French → English texts written by the server (statuses, confirmations, errors). */
export const serveurEn: Record<string, string> = {
    // Status labels
    "À l'étude": 'Under review',
    Livrée: 'Delivered',
    Retenue: 'Selected',
    Retenu: 'Selected',
    Déposée: 'Submitted',
    Placée: 'Placed',
    Clôturée: 'Closed',
    Proposé: 'Proposed',
    "Liste d'attente": 'Waiting list',
    Formée: 'Trained',
    Abandonnée: 'Withdrawn',
    Inactif: 'Inactive',

    // Confirmations
    'Abonnement activé : nouveau cycle lancé.':
        'Subscription activated: new cycle started.',
    'Abonnement créé.': 'Subscription created.',
    'Abonnement mis à jour.': 'Subscription updated.',
    'Abonnement renouvelé : nouveau cycle lancé.':
        'Subscription renewed: new cycle started.',
    'Clause mise à jour.': 'Clause updated.',
    'Compte apprenant supprimé.': 'Learner account deleted.',
    'Contrat mis à jour.': 'Contract updated.',
    'Contrat signé. Merci !': 'Contract signed. Thank you!',
    'Demande de paiement envoyée : validez-la sur votre téléphone ou sur la page du prestataire.':
        'Payment request sent: approve it on your phone or on the provider’s page.',
    'Demande mise à jour.': 'Request updated.',
    'Dossier supprimé.': 'File deleted.',
    'Formation mise à jour.': 'Training updated.',
    "Demande de renouvellement envoyée : l'équipe DAG vous envoie un devis.":
        'Renewal request sent: the DAG team will send you a quote.',
    "Inscription enregistrée : l'équipe DAG vous contacte pour le paiement et valide votre place.":
        'Enrolment recorded: the DAG team will contact you for the payment and confirm your place.',
    'Le paiement a échoué. Vous pouvez réessayer.':
        'The payment failed. You can try again.',
    'Paiement confirmé. Votre facture est réglée.':
        'Payment confirmed. Your invoice is settled.',
    'Paiement enregistré.': 'Payment recorded.',
    'Paiement mis à jour.': 'Payment updated.',
    'Préférences de notification mises à jour.':
        'Notification preferences updated.',
    'Session créée.': 'Session created.',
    'Session mise à jour.': 'Session updated.',
    'Session supprimée.': 'Session deleted.',
    'Une demande de renouvellement est déjà en cours.':
        'A renewal request is already in progress.',
    'Votre dossier a été mis à jour.': 'Your file has been updated.',
    'Vous êtes déjà inscrit à cette session.':
        'You are already enrolled in this session.',

    // Errors
    "Activez d'abord l'abonnement.": 'Activate the subscription first.',
    "La signature n'a pas pu être enregistrée.":
        'The signature could not be saved.',
    'Cette demande a déjà un contrat.': 'This request already has a contract.',
    'Cette demande a déjà une facture.': 'This request already has an invoice.',
    "La formule de cet abonnement n'existe plus : contactez l'équipe DAG.":
        'The plan of this subscription no longer exists: contact the DAG team.',
    'Ce compte est désactivé.': 'This account is deactivated.',
    'Cet abonnement ne peut pas être renouvelé pour le moment.':
        'This subscription cannot be renewed at the moment.',
    "Ce contrat n'a pas encore été signé.":
        'This contract has not been signed yet.',
    'Seule une facture payée peut être téléchargée.':
        'Only a paid invoice can be downloaded.',
    'Ce paiement a déjà été traité.':
        'This payment has already been processed.',
    "Cette offre n'accepte plus de candidatures.":
        'This offer no longer accepts applications.',

    // Notification titles, buttons and short texts
    'Votre attestation est disponible': 'Your certificate is available',
    'DAG vous propose un candidat': 'DAG suggests a candidate',
    'Votre inscription est validée': 'Your enrolment is confirmed',
    "Votre inscription n'a pas pu être retenue":
        'Your enrolment could not be accepted',
    "Ouvrir dans l'administration": 'Open in the administration',
    'Télécharger mon attestation': 'Download my certificate',
    'Voir le dossier du candidat': 'View the candidate’s file',
    'Lire et signer le contrat': 'Read and sign the contract',
    'Voir mon contrat': 'View my contract',
    'Voir et payer le devis': 'View and pay the quote',
    'Voir mes inscriptions': 'View my enrolments',
    'Voir mes séances': 'View my sessions',

    // Recruitment partnership validated by DAG
    'Votre entreprise est partenaire recrutement de DAG':
        'Your company is a DAG recruitment partner',
    "L'équipe DAG a validé votre entreprise comme partenaire recrutement. Vous pouvez maintenant publier vos offres d'emploi et recevoir les candidats que DAG vous transmet.":
        'The DAG team validated your company as a recruitment partner. You can now publish your job offers and receive the candidates DAG sends you.',
    'Publier une offre': 'Publish an offer',

    // Recruitment subscription
    'Votre accès recrutement est actif': 'Your recruitment access is active',
    'Chaque offre est vérifiée par DAG avant sa mise en ligne.':
        'Every offer is checked by DAG before it goes online.',
    'Les pages recrutement s’ouvrent avec un abonnement : choisissez la durée qui vous convient.':
        'The recruitment pages open with a subscription: choose the duration that suits you.',
    'Un paiement de votre abonnement est déjà en cours : finalisez-le depuis la facture.':
        'A payment of your subscription is already in progress: complete it from the invoice.',
    'L’abonnement recrutement est réservé aux entreprises et organisations.':
        'The recruitment subscription is reserved to companies and organisations.',

    // Partnerships
    'Digital Agency Group vous propose un partenariat':
        'Digital Agency Group proposes a partnership to you',
    'Votre demande de partenariat est acceptée':
        'Your partnership request is accepted',
    'Une fois votre signature validée par DAG, votre structure devient « Partenaire officiel de Digital Agency Group ».':
        'Once DAG has validated your signature, your organisation becomes an “Official partner of Digital Agency Group”.',
    'Votre demande de partenariat n’a pas été retenue':
        'Your partnership request was not accepted',
    'Vous pourrez présenter une nouvelle demande depuis votre espace lorsque votre situation aura évolué.':
        'You can submit a new request from your space once your situation has changed.',
    'Voir ma demande': 'View my request',
    'Vous êtes Partenaire officiel de Digital Agency Group':
        'You are an Official partner of Digital Agency Group',
    'Votre contrat signé est disponible dans votre espace, rubrique « Partenariat DAG ».':
        'Your signed contract is available in your space, under “DAG partnership”.',
    'Voir mon partenariat': 'View my partnership',
    'Votre contrat de partenariat avec Digital Agency Group est disponible dans votre espace. Sa signature est nécessaire pour que le partenariat prenne effet.':
        'Your partnership contract with Digital Agency Group is available in your space. It must be signed for the partnership to take effect.',
    'Le partenariat est réservé aux entreprises et organisations.':
        'Partnerships are reserved to companies and organisations.',
    'Un partenariat est déjà en cours avec cette structure.':
        'A partnership is already under way with this organisation.',
    'Choisissez au moins un domaine de collaboration.':
        'Choose at least one area of collaboration.',
    'Présentez votre structure et votre projet de partenariat.':
        'Present your organisation and your partnership project.',
    'Présentez votre projet en quelques phrases (30 caractères au moins).':
        'Present your project in a few sentences (at least 30 characters).',
    'Indiquez le motif du refus : il est transmis à la structure.':
        'Give the reason for the refusal: it is sent to the organisation.',
    'Expliquez le refus en une phrase au moins.':
        'Explain the refusal in at least one sentence.',
    'Choisissez la structure à qui proposer le partenariat.':
        'Choose the organisation to propose the partnership to.',
    'Contrat signé, merci ! L’équipe DAG le valide, puis votre partenariat devient actif.':
        'Contract signed, thank you! The DAG team validates it, then your partnership becomes active.',
    'Cette demande de partenariat a déjà été traitée.':
        'This partnership request has already been handled.',

    // Partner logos
    'Indiquez le nom du partenaire.': 'Give the partner’s name.',
    'Importez le logo du partenaire.': 'Upload the partner’s logo.',
    'Le logo doit être une image PNG, JPG ou WebP.':
        'The logo must be a PNG, JPG or WebP image.',
    'Le logo ne doit pas dépasser 2 Mo.': 'The logo must not exceed 2 MB.',
    'Le logo est trop petit : 80 × 40 pixels au moins.':
        'The logo is too small: at least 80 × 40 pixels.',
    'Le lien doit être une adresse web complète (https://…).':
        'The link must be a full web address (https://…).',
};
