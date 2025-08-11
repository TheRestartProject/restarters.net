import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import { resolve } from 'path';
import { readFileSync } from 'fs';
import laravelTranslator from 'laravel-translator/vite';

// Parse .env file to get APP_URL
function getAppUrl() {
    try {
        const envFile = readFileSync('.env', 'utf8');
        const appUrlMatch = envFile.match(/^APP_URL=(.+)$/m);
        if (appUrlMatch) {
            const url = new URL(appUrlMatch[1]);
            return url.hostname;
        }
    } catch (error) {
        console.warn('Could not read .env file:', error.message);
    }
    return 'localhost';
}

// Use environment variable if set, otherwise get from APP_URL
const host = process.env.VITE_HOST || getAppUrl();

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/global/css/app.scss',
                'resources/global/js/app.js',
                'resources/wiki/css/app.scss',
                'resources/wiki/js/wiki.js',
            ],
            refresh: true,
            devUrl: `http://${host}:5173`,
        }),
        vue(),
        laravelTranslator(),
        // Plugin to handle broken source maps and malformed JS from legacy packages
        {
            name: 'fix-legacy-packages',
            load(id) {
                // Skip loading source maps for packages with known issues
                if (id.includes('vue2-dropzone') && id.endsWith('.js.map')) {
                    return null;
                }
                if (id.includes('vue2-dropzone') && id.includes('vue2Dropzone.js.map')) {
                    return null;
                }
                if (id.includes('tokenfield') && id.endsWith('.js.map')) {
                    return null;
                }
                if (id.includes('slick-carousel') && id.endsWith('.js.map')) {
                    return null;
                }
            },
            transform(code, id) {
                // Remove source map references from files with broken source maps
                if (id.includes('vue2-dropzone') && (id.endsWith('.js') || id.includes('vue2Dropzone.js'))) {
                    return {
                        code: code.replace(/\/\/# sourceMappingURL=.*\.js\.map/g, ''),
                        map: null
                    };
                }
                
            }
        }
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: host,
            port: 5173,
        },
        watch: {
            usePolling: true,
            interval: 1000,
        },
        cors: true,
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm.js',
            '@': resolve(__dirname, 'resources/js'),
            // Fix Sentry 6.7.2 globalThis resolution issue
            './globalThis': 'globalThis'
        },
    },
    define: {
        global: 'globalThis',
        // Make jQuery available globally for packages that expect it
        $: 'jQuery',
        jQuery: 'jQuery',
        // Define process for packages that expect Node.js environment
        'process.env': JSON.stringify(process.env),
        'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development'),
    },
    optimizeDeps: {
        force: true,
        include: [
            'lodash/clone',
            'lodash/includes', 
            'lodash/isEmpty',
            'lodash/reject',
            'lodash/reverse',
            'lodash/findIndex'
        ],
        exclude: [
            'vue2-leaflet', 
            'vue2-dropzone',
        ]
    },
    build: {
        rollupOptions: {
            external: [
            ],
            onwarn(warning, warn) {
                // Suppress warnings about unresolved asset references
                if (warning.code === 'UNRESOLVED_IMPORT' && 
                    (warning.source?.includes('/images/') || warning.source?.includes('/icons/'))) {
                    return;
                }
                // Suppress asset resolution warnings from message content
                if (warning.message?.includes('/images/') || warning.message?.includes('/icons/')) {
                    return;
                }
                // Suppress CSS asset warnings that contain "didn't resolve at build time"
                if (warning.message?.includes("didn't resolve at build time")) {
                    return;
                }
                warn(warning);
            }
        }
    },
    css: {
        devSourcemap: true
    },
    logLevel: 'warn',
    customLogger: {
        info(msg) {
            // Suppress asset resolution info messages for images, icons, and CSS variables
            if (msg.includes("didn't resolve at build time") && 
                (msg.includes('/images/') || msg.includes('/icons/') || msg.includes('$slick-font-path') || msg.includes('$slick-loader-path'))) {
                return;
            }
            console.info(msg);
        },
        warn(msg) {
            // Suppress asset resolution warnings for images, icons, and CSS variables
            if (msg.includes("didn't resolve at build time") && 
                (msg.includes('/images/') || msg.includes('/icons/') || msg.includes('$slick-font-path') || msg.includes('$slick-loader-path'))) {
                return;
            }
            console.warn(msg);
        },
        error: console.error,
        warnOnce(msg) {
            // Suppress asset resolution warnings for images, icons, and CSS variables
            if (msg.includes("didn't resolve at build time") && 
                (msg.includes('/images/') || msg.includes('/icons/') || msg.includes('$slick-font-path') || msg.includes('$slick-loader-path'))) {
                return;
            }
            console.warn(msg);
        },
        hasWarned: false
    },
});