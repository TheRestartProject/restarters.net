let mix = require('laravel-mix');
let webpack = require('webpack');
const WebpackShellPlugin = require('webpack-shell-plugin');
require('laravel-mix-bundle-analyzer');

if (!mix.inProduction()) {
    mix.bundleAnalyzer({
        analyzerMode: 'static',
        openAnalyzer: false
    });
}

mix.webpackConfig({
    plugins: [
        new webpack.IgnorePlugin(/^codemirror$/),
        // Build a JS translation file that corresponds to our PHP lang/ folder.
        new WebpackShellPlugin({onBuildStart:['php artisan lang:js --no-lib --quiet resources/js/translations.js'], onBuildEnd:['php artisan translations:check']})
    ]
});
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// mix.scripts([
//     'node_modules/js-cookie/src/js.cookie.js',
//     'resources/js/gdpr-cookie-notice/templates.js',
//     'resources/js/gdpr-cookie-notice/script.js',
//     'resources/js/gdpr-cookie-notice/en.js'
// ], 'public/js/gdpr-cookie-notice.js');

mix.js('resources/js/app.js', 'public/js')
   .vue()
   .sass('resources/sass/app.scss', 'public/css')
   .browserSync({
        proxy: 'https://restarters.test'
    });

mix.js('resources/global/js/app.js', 'public/global/js')
   .sass('resources/global/css/app.scss', 'public/global/css');

mix.js('resources/wiki/js/wiki.js', 'public/js/wiki.js')
  .sass('resources/wiki/css/app.scss', 'public/css/wiki.css');