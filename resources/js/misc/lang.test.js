/**
 * Unit tests for lang-utils.js (pure translation / pluralisation logic).
 *
 * lang.js wraps these utilities but adds Vite/Sentry deps that are awkward
 * to test directly.  Testing the utils directly gives full coverage of the
 * logic we care about.
 */
import { translateWithLocale, choiceWithLocale } from '../mixins/lang-utils.js';

// ---------------------------------------------------------------------------
// choiceWithLocale — plain "singular|plural" strings
// ---------------------------------------------------------------------------
describe('choiceWithLocale — plain singular|plural', () => {
    const translations = {
        en: { events: { not_counting: 'impact is|impact are' } },
    };

    it('returns singular form (first segment) when count is 1', () => {
        expect(choiceWithLocale(translations, 'en', 'events.not_counting', 1)).toBe('impact is');
    });

    it('returns plural form (last segment) when count is 2', () => {
        expect(choiceWithLocale(translations, 'en', 'events.not_counting', 2)).toBe('impact are');
    });

    it('returns plural form when count is 0', () => {
        expect(choiceWithLocale(translations, 'en', 'events.not_counting', 0)).toBe('impact are');
    });

    it('never returns the raw pipe-delimited string', () => {
        for (const count of [0, 1, 2, 10]) {
            expect(choiceWithLocale(translations, 'en', 'events.not_counting', count)).not.toContain('|');
        }
    });
});

// ---------------------------------------------------------------------------
// choiceWithLocale — {n} and [n,m] explicit qualifiers still work
// ---------------------------------------------------------------------------
describe('choiceWithLocale — explicit {n} / [n,m] qualifiers', () => {
    const translations = {
        en: { test: { key: '{0} none|{1} one|[2,*] many' } },
    };

    it('handles {0} exact match', () => {
        expect(choiceWithLocale(translations, 'en', 'test.key', 0)).toBe('none');
    });

    it('handles {1} exact match', () => {
        expect(choiceWithLocale(translations, 'en', 'test.key', 1)).toBe('one');
    });

    it('handles [2,*] range match', () => {
        expect(choiceWithLocale(translations, 'en', 'test.key', 5)).toBe('many');
    });
});

// ---------------------------------------------------------------------------
// translateWithLocale — top-level JSON locale keys (e.g. category names)
// ---------------------------------------------------------------------------
describe('translateWithLocale — top-level locale keys', () => {
    const translations = {
        fr: { 'Desktop computer': 'Ordinateur de bureau' },
        en: { 'Desktop computer': 'Desktop computer' },
    };

    it('returns the translated value for a top-level key in the requested locale', () => {
        expect(translateWithLocale(translations, 'fr', 'Desktop computer')).toBe('Ordinateur de bureau');
    });

    it('falls back to English when the locale is not in the table', () => {
        expect(translateWithLocale(translations, 'de', 'Desktop computer')).toBe('Desktop computer');
    });

    it('translates nested keys (e.g. partials.to_be_recycled)', () => {
        const t = { fr: { partials: { to_be_recycled: ':value objet à recycler|:value objets à recycler' } } };
        const result = translateWithLocale(t, 'fr', 'partials.to_be_recycled', { value: 3 });
        expect(result).toContain('3');
    });
});
