import * as Sentry from "@sentry/vue";

// Set up internationalisation using modern laravel-translator
import { __ as translate } from 'laravel-translator';
export const Lang = { get: translate }

export default {
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