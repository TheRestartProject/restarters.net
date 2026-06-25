/**
 * Pure translation utilities — no Vite/Sentry dependencies.
 * Accepts translations and locale as explicit parameters so they can be
 * injected in tests without mocking import.meta.env.
 */

export function translateWithLocale(translations, locale, key, values = {}) {
    const parts = key.split('.');
    let translation;

    if (translations[locale]) {
        translation = translations[locale];
    } else if (translations['en']) {
        translation = translations['en'];
    } else {
        return key;
    }

    for (const part of parts) {
        if (translation && typeof translation === 'object' && part in translation) {
            translation = translation[part];
        } else {
            return key;
        }
    }

    if (typeof translation === 'string' && Object.keys(values).length > 0) {
        return translation.replace(/:(\w+)/g, (match, param) => {
            return values[param] !== undefined ? values[param] : match;
        });
    }

    return typeof translation === 'string' ? translation : key;
}

export function choiceWithLocale(translations, locale, key, count, values = {}) {
    const parts = key.split('.');
    let rawTranslation;

    if (translations[locale]) {
        rawTranslation = translations[locale];
    } else if (translations['en']) {
        rawTranslation = translations['en'];
    } else {
        return key;
    }

    for (const part of parts) {
        if (rawTranslation && typeof rawTranslation === 'object' && part in rawTranslation) {
            rawTranslation = rawTranslation[part];
        } else {
            return key;
        }
    }

    if (typeof rawTranslation !== 'string') {
        return key;
    }

    if (rawTranslation.includes('|')) {
        const segments = rawTranslation.split('|');

        // Default for plain "singular|plural" (no explicit {n} or [n,m] qualifiers):
        // count === 1 → first segment, otherwise → last segment.
        let selectedSegment = count === 1 ? segments[0] : segments[segments.length - 1];

        for (const segment of segments) {
            // Match {n} syntax for exact values
            const exactMatch = segment.match(/^\{(\d+)\}\s*(.*)$/);
            if (exactMatch) {
                const exactNum = parseInt(exactMatch[1], 10);
                if (count === exactNum) {
                    selectedSegment = exactMatch[2];
                    break;
                }
                continue;
            }

            // Match [n,m] or [n,*] syntax for ranges
            const rangeMatch = segment.match(/^\[(\d+),(\d+|\*)\]\s*(.*)$/);
            if (rangeMatch) {
                const min = parseInt(rangeMatch[1], 10);
                const max = rangeMatch[2] === '*' ? Infinity : parseInt(rangeMatch[2], 10);
                if (count >= min && count <= max) {
                    selectedSegment = rangeMatch[3];
                    break;
                }
                continue;
            }
        }

        return selectedSegment.replace(/:(\w+)/g, (match, param) => {
            if (param === 'count') return count;
            return values[param] !== undefined ? values[param] : match;
        });
    }

    return translateWithLocale(translations, locale, key, { ...values, count });
}
