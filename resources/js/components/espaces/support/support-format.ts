export const SUPPORT_STATUT_CHIP: Record<string, string> = {
    OUVERT: 'chip-orange',
    REPONDU: 'chip-blue',
    CLOS: 'chip-gray',
};

export function formaterDateSupport(dateIso: string | null): string {
    if (dateIso === null) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateIso));
}
