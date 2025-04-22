import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    plugins: [
        laravel({
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
    ],
});
