import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import { watchAndRun } from 'vite-plugin-watch-and-run';

export default defineConfig({
    optimizeDeps: {
        exclude: ['codemirror'],
    },
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm.js',
            '@': '/resources/js'
        }
    },
    plugins: [
        // Workaround from https://github.com/laravel/vite-plugin/pull/189#issuecomment-1416704995
        laravel.default({
            input: [
                'resources/global/css/app.scss',
                'resources/sass/app.scss',
                'resources/wiki/css/app.scss',
                'resources/global/js/app.js',
                'resources/js/app.js',
                'resources/wiki/js/wiki.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        watchAndRun([
            {
                name: 'translation-watcher',
                watch: 'lang/**/*.{php,json}',
                run: 'php artisan lang:js --no-lib resources/js/translations.js',
                delay: 300
            }
        ])
    ],
});
