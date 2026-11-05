import { describe, expect, it } from 'vitest';
import { traduire } from './traduire';

describe('traduire', () => {
    it('translates a known text and keeps the surrounding spaces', () => {
        expect(traduire('Se connecter')).toBe('Log in');
        expect(traduire('  Se connecter ')).toBe('  Log in ');
    });

    it('leaves names and unknown content untouched', () => {
        expect(traduire('Kiro Digital Solutions')).toBe('Kiro Digital Solutions');
        expect(traduire('150 000')).toBe('150 000');
    });

    it('translates texts with a variable part', () => {
        expect(traduire('Il y a 5 min')).toBe('5 min ago');
        expect(traduire('Il y a 2 jours')).toBe('2 days ago');
        expect(traduire('Paiement reçu — FAC-001')).toBe('Payment received — FAC-001');
        expect(traduire('Abonnements — Administration')).toBe('Subscriptions — Administration');
        expect(traduire('Contrat CTR-12')).toBe('Contract CTR-12');
    });

    it('translates French month names in dates', () => {
        expect(traduire('1 sept. 2026')).toBe('1 Sep 2026');
        expect(traduire('15 novembre')).toBe('15 November');
    });

    it('shares the translation of a label with its colon', () => {
        expect(traduire('Description :')).toBe('Description :');
        expect(traduire('Sujet :')).toBe('Subject :');
    });
});

describe('numbered steps', () => {
    it('keeps the number and translates the step', () => {
        expect(traduire('1. Choisissez votre formation')).toBe('1. Choose your training');
    });
});

describe('catalogue content', () => {
    it('translates prices, durations and seeded descriptions', () => {
        expect(traduire('50 000–80 000 / mois')).toBe('50 000–80 000 / month');
        expect(traduire('À partir de 700 000')).toBe('From 700 000');
        expect(traduire('3 semaines')).toBe('3 weeks');
        expect(traduire('Hébergement web')).toBe('Web hosting');
    });
});

describe('titles and months', () => {
    it('translates the page title and the month filters', () => {
        expect(traduire('Connexion - DAG — Digital Agency Group')).toBe('Sign in - DAG — Digital Agency Group');
        expect(traduire('Novembre 2026')).toBe('November 2026');
    });
});
