// Mock for lang.js to avoid import.meta.env issues in Jest

const translations = {
    devices: {
        weight: 'Weight',
        required_impact: 'Required for impact calculation',
        optional_impact: 'Optional for impact calculation'
    }
};

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
                console.error("Missing translation " + key)
                return key
            }
        }
    }
}