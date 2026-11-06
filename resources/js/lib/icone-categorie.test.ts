import {
    Briefcase,
    Code2,
    GraduationCap,
    Hospital,
    Megaphone,
    PenTool,
    ShieldCheck,
    TrendingUp,
    Users,
} from 'lucide-react';
import { describe, expect, it } from 'vitest';
import { iconeCategorie } from './icone-categorie';

describe('iconeCategorie', () => {
    it('keeps the icons of the existing categories', () => {
        expect(iconeCategorie('Community management', Briefcase)).toBe(Users);
        expect(iconeCategorie('Création de contenu', Briefcase)).toBe(PenTool);
        expect(iconeCategorie('Stratégie & publicité', Briefcase)).toBe(
            TrendingUp,
        );
        expect(iconeCategorie('Création & communication', Briefcase)).toBe(
            Megaphone,
        );
        expect(iconeCategorie('Technique & digital', Briefcase)).toBe(Code2);
    });

    it('picks an icon from the words of a new category, ignoring accents and case', () => {
        expect(iconeCategorie('Services aux HÔPITAUX', Briefcase)).toBe(
            Hospital,
        );
        expect(iconeCategorie('cybersecuriter130', Briefcase)).toBe(
            ShieldCheck,
        );
        expect(iconeCategorie('Formation', Briefcase)).toBe(GraduationCap);
    });

    it('falls back to the given icon when no word matches', () => {
        expect(iconeCategorie('Divers', Briefcase)).toBe(Briefcase);
    });
});
