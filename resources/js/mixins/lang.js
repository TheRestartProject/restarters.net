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

function choice(key, count, values = {}) {
    // Simple pluralization helper
    // Laravel's pluralization uses count parameter to determine singular/plural
    const translation = translate(key, { ...values, count });

    // If translation contains pipes, handle pluralization
    if (typeof translation === 'string' && translation.includes('|')) {
        const parts = translation.split('|');
        // Simple rule: 0 or 1 uses first part, > 1 uses second part
        return count <= 1 ? parts[0] : (parts[1] || parts[0]);
    }

    return translation;
}

export const Lang = { get: translate, choice: choice }

export default {
    beforeCreate() {
        this.$lang = { get: translate, choice: choice }
    },
    methods: {
        __(key, values) {
            try {
                return translate(key, values)
            } catch (error) {
                Sentry.captureMessage("Missing translation " + key)
                return key
            }
        }
    }
}