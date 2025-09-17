import * as Sentry from "@sentry/vue";

const translations = import.meta.env.VITE_LARAVEL_TRANSLATIONS || {};

function translate(key, values = {}) {
    const parts = key.split('.');
    let translation = translations;

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

export const Lang = { get: translate }

export default {
    beforeCreate() {
        this.$lang = { get: translate }
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