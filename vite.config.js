import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve, dirname } from 'path';
import { existsSync, readFileSync, mkdirSync, copyFileSync } from 'fs';

// Post-cutover this build covers only what Laravel still serves directly:
// the embeddable stats widgets and the MediaWiki skin assets. The SPA in
// client/ has its own Nuxt/Vite build. The surviving JS is jQuery-only —
// no Vue, no translations plugin, no select2 shims.

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

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/global/js/app.js',
                'resources/global/js/widgets/stats-share.js',
                'resources/global/css/app.scss',
                'resources/wiki/js/wiki.js',
                'resources/wiki/css/app.scss'
            ],
            refresh: true,
        }),
        legacyCssAliases(),
    ],
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
