import { adminEn } from './en/admin';
import { authEn } from './en/auth';
import { contenuEn } from './en/contenu';
import { espacesEn } from './en/espaces';
import { publicEn } from './en/public';
import { serveurEn } from './en/serveur';

/** French text → English text, every area of the application together. */
export const dictionnaire: Map<string, string> = new Map(
    [publicEn, authEn, espacesEn, adminEn, serveurEn, contenuEn].flatMap((table) =>
        Object.entries(table),
    ),
);
