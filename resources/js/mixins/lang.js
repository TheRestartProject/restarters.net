import * as Sentry from "@sentry/vue";

const translations = import.meta.env.VITE_LARAVEL_TRANSLATIONS || {};

function translate(key, values = {}) {
    const parts = key.split('.');
    let translation = translations;

    // Get the current locale from Laravel (it should be set in a meta tag or similar)
    const locale = document.documentElement.lang || 'en';

    // Start from the locale-specific translations
    if (translations[locale]) {
        translation = translations[locale];
    } else if (translations['en']) {
        // Fallback to English if locale not found
        translation = translations['en'];
        console.warn(`Locale ${locale} not found, using English`);
    } else {
        console.error('No translations available');
        return key;
    }

    for (const part of parts) {
        if (translation && typeof translation === 'object' && part in translation) {
            translation = translation[part];
        } else {
            console.warn(`Translation not found for key: ${key} (missing part: ${part})`);
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

function getLocale() {
    return document.documentElement.lang || 'en';
}

function choice(key, count, values = {}) {
    // Get raw translation without substitution first
    const parts = key.split('.');
    let rawTranslation = translations;
    const locale = document.documentElement.lang || 'en';

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

    // Handle Laravel's pluralization syntax: {0} text|{1} text|[2,*] text
    if (rawTranslation.includes('|')) {
        const segments = rawTranslation.split('|');
        let selectedSegment = segments[segments.length - 1]; // Default to last segment

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

        // Apply parameter substitution
        return selectedSegment.replace(/:(\w+)/g, (match, param) => {
            if (param === 'count') return count;
            return values[param] !== undefined ? values[param] : match;
        });
    }

    return translate(key, { ...values, count });
}

export const Lang = { get: translate, choice: choice, getLocale: getLocale }

export default {
    beforeCreate() {
        this.$lang = { get: translate, choice: choice, getLocale: getLocale }
    },
    methods: {
        __(key, values) {
            try {
                // If values contains a 'count' parameter, use pluralization
                if (values && values.count !== undefined) {
                    return choice(key, values.count, values)
                }
                return translate(key, values)
            } catch (error) {
                Sentry.captureMessage("Missing translation " + key)
                return key
            }
        }
    }
}