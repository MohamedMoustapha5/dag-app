import { Crown, Layers, Megaphone, Rocket, TrendingUp } from 'lucide-react';
import { describe, expect, it } from 'vitest';
import { iconeFormule } from './icone-formule';

describe('iconeFormule', () => {
    it('gives each tier its own icon', () => {
        expect(iconeFormule('Essentiel')).toBe(Rocket);
        expect(iconeFormule('Croissance')).toBe(TrendingUp);
        expect(iconeFormule('Premium')).toBe(Crown);
    });

    it('falls back on what the name describes, then on a stack', () => {
        expect(iconeFormule('Pack Communication')).toBe(Megaphone);
        expect(iconeFormule('Formule XYZ')).toBe(Layers);
    });
});
