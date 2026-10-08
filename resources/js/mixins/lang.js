import * as Sentry from "@sentry/vue";
import { translateWithLocale, choiceWithLocale } from './lang-utils.js';

const translations = import.meta.env.VITE_LARAVEL_TRANSLATIONS || {};

function getLocale() {
    return document.documentElement.lang || 'en';
}

function translate(key, values = {}) {
    return translateWithLocale(translations, getLocale(), key, values);
}

function choice(key, count, values = {}) {
    return choiceWithLocale(translations, getLocale(), key, count, values);
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
        },
        // Translation strings can contain markup, and the translator interpolates
        // :placeholder values into it without escaping.  Any value that comes from the
        // database has to be escaped by the caller before it reaches a v-html binding.
        escapeHtml(value) {
            if (value === null || value === undefined) {
                return ''
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
        }
    }
}
