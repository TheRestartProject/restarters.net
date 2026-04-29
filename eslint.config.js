import pluginVue from 'eslint-plugin-vue';

const browserGlobals = {
    // Core browser APIs
    window: 'readonly',
    document: 'readonly',
    console: 'readonly',
    navigator: 'readonly',
    location: 'readonly',
    history: 'readonly',
    localStorage: 'readonly',
    sessionStorage: 'readonly',
    setTimeout: 'readonly',
    clearTimeout: 'readonly',
    setInterval: 'readonly',
    clearInterval: 'readonly',
    requestAnimationFrame: 'readonly',
    CustomEvent: 'readonly',
    Event: 'readonly',
    Element: 'readonly',
    Node: 'readonly',
    Image: 'readonly',
    File: 'readonly',
    FileReader: 'readonly',
    FormData: 'readonly',
    URL: 'readonly',
    URLSearchParams: 'readonly',
    XMLHttpRequest: 'readonly',
    MutationObserver: 'readonly',
    getComputedStyle: 'readonly',
    alert: 'readonly',
    confirm: 'readonly',
    fetch: 'readonly',
    // CDN libraries used in this project
    jQuery: 'readonly',
    $: 'readonly',
    google: 'readonly',   // Google Maps
    L: 'readonly',        // Leaflet
    axios: 'readonly',    // axios (also imported via npm in Vue files)
    tinysort: 'readonly', // TinySort
    gdprCookieNotice: 'readonly',
    // Inline-script globals set by Blade templates
    restarters: 'readonly',
    Cookies: 'readonly',
    // Vite/build
    process: 'readonly',
};

export default [
    // Vue SFCs
    ...pluginVue.configs['flat/vue2-essential'],

    // Our own plain JS (misc/ and app.js) — skip vendor/minified files
    {
        files: [
            'resources/js/app.js',
            'resources/js/misc/**/*.js',
            'resources/js/mixins/**/*.js',
            'resources/js/store/**/*.js',
            'resources/js/components/**/*.js',
        ],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'module',
            globals: browserGlobals,
        },
        rules: {
            'no-undef': 'error',
            'no-var': 'warn',
        },
    },

    // Vue SFCs — globals on top of vue plugin defaults
    {
        files: ['resources/js/**/*.vue'],
        languageOptions: {
            globals: browserGlobals,
        },
        rules: {
            'no-undef': 'error',
        },
    },

    // Jest test files
    {
        files: ['resources/js/**/*.test.js', 'resources/js/**/*.spec.js'],
        languageOptions: {
            globals: {
                test: 'readonly',
                it: 'readonly',
                describe: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                jest: 'readonly',
                require: 'readonly',
                module: 'readonly',
            },
        },
    },

    {
        ignores: [
            'public/**',
            // Vendor / minified / generated files in resources/js root
            'resources/js/bootstrap*.js',
            'resources/js/jquery*.js',
            'resources/js/vendor.js',
            'resources/js/translations.js',
            'resources/js/gdpr-cookie-notice/**',
            'node_modules/**',
            'vendor/**',
        ],
    },
];
