import * as Sentry from "@sentry/vue";

// Set up internationalisation.  translations.js is built in webpack.mix.js from the PHP lang folder.
import lang from 'lang.js';
import * as translations from '../translations.js';
export const Lang = new lang()
Lang.setFallback('en')
Lang.setMessages(translations)

export default {
    computed: {
        $lang() {
            // We want this to be available in all components.
            return Lang
        }
    },
    methods: {
        __(key, values) {
            if (this.$lang.has(key)) {
                return this.$lang.get(key, values)
            } else {
                Sentry.captureMessage("Missing translation " + key)
            }
        }
    }
}