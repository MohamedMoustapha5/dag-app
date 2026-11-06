import {
    Building2,
    Crown,
    Gem,
    Layers,
    Rocket,
    TrendingUp,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { iconeCategorie } from '@/lib/icone-categorie';
import { normaliser } from '@/lib/recherche';

/**
 * Icon of a subscription formule, picked from its name: the usual tier
 * names get a tier icon (Essentiel a rocket, Premium a crown…), any other
 * formule the icon of what its name describes, and failing that a stack.
 */
const PALIERS: [RegExp, LucideIcon][] = [
    [/essentiel|starter|basique|\bbase\b|decouverte|\bmini/, Rocket],
    [/croissance|\bpro\b|standard|avance|growth|boost/, TrendingUp],
    [/premium|\bgold\b|\bor\b|prestige|\bvip\b|excellence|\bmax/, Crown],
    [/\belite|platin|diamant|ultime|illimite/, Gem],
    [/entreprise|business|corporate|sur mesure/, Building2],
];

export function iconeFormule(nom: string): LucideIcon {
    const texte = normaliser(nom);

    for (const [motif, icone] of PALIERS) {
        if (motif.test(texte)) {
            return icone;
        }
    }

    return iconeCategorie(nom, Layers);
}
