export type Surveillance = {
    renouvellementsDemandes: number;
    bientot: number;
    expires: number;
};

/** Short sentences describing what the team must watch on subscriptions. */
export function phrasesSurveillance(resume: Surveillance): string[] {
    const phrases: string[] = [];

    if (resume.renouvellementsDemandes > 0) {
        phrases.push(
            `${resume.renouvellementsDemandes} renouvellement${resume.renouvellementsDemandes > 1 ? 's' : ''} demandé${resume.renouvellementsDemandes > 1 ? 's' : ''} par un client`,
        );
    }
    if (resume.expires > 0) {
        phrases.push(
            `${resume.expires} abonnement${resume.expires > 1 ? 's' : ''} expiré${resume.expires > 1 ? 's' : ''}`,
        );
    }
    if (resume.bientot > 0) {
        phrases.push(
            `${resume.bientot} abonnement${resume.bientot > 1 ? 's' : ''} arrivant à échéance`,
        );
    }

    return phrases;
}
