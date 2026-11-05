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
    "Ce contrat n'a pas encore été signé.": 'This contract has not been signed yet.',
    'Seule une facture payée peut être téléchargée.':
        'Only a paid invoice can be downloaded.',
    'Ce paiement a déjà été traité.': 'This payment has already been processed.',
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
};
