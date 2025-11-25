import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import laravelTranslations from 'vite-plugin-laravel-translations';
import { resolve } from 'path';

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
        laravelTranslations(),
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
            host: 'localhost',
        },
    },
});