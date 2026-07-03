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
        }
    }
}
