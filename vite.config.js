import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import laravelTranslations from 'vite-plugin-laravel-translations';
import { resolve, dirname } from 'path';
import { existsSync, readFileSync, readdirSync, mkdirSync, copyFileSync } from 'fs';

// The MediaWiki wiki references our CSS at unhashed legacy public URLs
// (/css/wiki.css, /global/css/app.css). These paths pre-date the Mix -> Vite
// migration; Vite outputs hashed files under /build/, so we copy the relevant
// built CSS back to the legacy locations to preserve those public URLs.
function legacyCssAliases() {
    return {
        name: 'legacy-css-aliases',
        apply: 'build',
        writeBundle() {
            const manifestPath = resolve(__dirname, 'public/build/manifest.json');
            if (!existsSync(manifestPath)) return;
            const manifest = JSON.parse(readFileSync(manifestPath, 'utf-8'));
            const aliases = {
                'resources/wiki/css/app.scss': 'public/css/wiki.css',
                'resources/global/css/app.scss': 'public/global/css/app.css',
            };
            for (const [entry, dest] of Object.entries(aliases)) {
                const built = manifest[entry] && manifest[entry].file;
                if (!built) continue;
                const src = resolve(__dirname, 'public/build', built);
                const out = resolve(__dirname, dest);
                if (!existsSync(src)) continue;
                mkdirSync(dirname(out), { recursive: true });
                copyFileSync(src, out);
            }
        },
    };
}

// vite-plugin-laravel-translations@0.3.1's `includeJson` option is broken: its JSON
// branch does `await import(`${cwd}/${absolutePath}`)` (doubled path + a missing JSON
// import attribute), which throws on Node 18+ and crashes Vite (no assets -> app 500s).
// Device category/cluster names live as top-level keys in lang/<locale>.json
// (e.g. "Desktop computer": "Ordinateur de bureau") and the Vue `__()` helper looks
// them up at the locale root. So we run the plugin for the PHP translations and
// deep-merge the JSON files in ourselves via plain JSON.parse (no dynamic import).
function deepMergeTranslations(a, b) {
    if (a && b && typeof a === 'object' && typeof b === 'object'
        && !Array.isArray(a) && !Array.isArray(b)) {
        const out = { ...a };
        for (const key of Object.keys(b)) {
            out[key] = key in a ? deepMergeTranslations(a[key], b[key]) : b[key];
        }
        return out;
    }
    return b;
}

function laravelTranslationsWithJson() {
    const DEFINE_KEY = 'import.meta.env.VITE_LARAVEL_TRANSLATIONS';
    return {
        name: 'laravel-translations-with-json',
        async config() {
            const base = await (await laravelTranslations()).config();
            const translations = base.define[DEFINE_KEY];
            const langDir = resolve(__dirname, 'lang');
            for (const file of readdirSync(langDir)) {
                if (!file.endsWith('.json')) continue;
                const locale = file.replace(/\.json$/, '');
                const json = JSON.parse(readFileSync(resolve(langDir, file), 'utf-8'));
                translations[locale] = deepMergeTranslations(translations[locale] || {}, json);
            }
            return { define: { [DEFINE_KEY]: translations } };
        },
        handleHotUpdate({ file, server }) {
            if (/lang[\\/].*\.(?:php|json)$/.test(file)) server.restart();
        },
    };
}

export default defineConfig({
    define: {
        'process.env': {},
        // Make jQuery available globally for Select2
        'global': 'window',
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/sass/app.scss',
                'resources/global/js/app.js',
                'resources/global/css/app.scss',
                'resources/wiki/js/wiki.js',
                'resources/wiki/css/app.scss'
            ],
            refresh: true,
        }),
        vue(),
        laravelTranslationsWithJson(),
        legacyCssAliases(),
        {
            name: 'jquery-global',
            transform(code, id) {
                // Inject jQuery from window for select2
                if (id.includes('select2')) {
                    return code.replace(
                        /require\(['"]jquery['"]\)/g,
                        'window.jQuery'
                    );
                }
            }
        }
    ],
    resolve: {
        alias: {
            'vue': resolve(__dirname, 'node_modules/vue/dist/vue.esm.js'),
            '@': resolve(__dirname, 'resources/js'),
            'lodash/clone': 'lodash-es/clone',
            'lodash/includes': 'lodash-es/includes',
            'lodash/isEmpty': 'lodash-es/isEmpty',
            'lodash/reject': 'lodash-es/reject',
            'lodash/reverse': 'lodash-es/reverse',
            'lodash/findIndex': 'lodash-es/findIndex',
            // 'lodash/trimEnd': 'lodash-es/trimEnd', - REMOVED: breaks text-clipper
            'lodash/zip': 'lodash-es/zip',
            'lodash/compact': 'lodash-es/compact',
            'lodash/max': 'lodash-es/max',
            'lodash/times': 'lodash-es/times',
            // 'lodash/trimStart': 'lodash-es/trimStart', - REMOVED: may break text-clipper
            'lodash/padEnd': 'lodash-es/padEnd',
        },
    },
    build: {
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
        },
        cors: true,
    },
});