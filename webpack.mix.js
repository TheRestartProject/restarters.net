let mix = require('laravel-mix');
let webpack = require('webpack');
const WebpackShellPluginNext = require('webpack-shell-plugin-next');

mix.webpackConfig({
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader'
      }
    ]
  },
  plugins: [
    // Build a JS translation file that corresponds to our PHP lang/ folder.
    new WebpackShellPluginNext({onBuildStart:['php artisan lang:js --no-lib --quiet resources/js/translations.js'], onBuildEnd:[]})
  ]
});

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .browserSync({
        proxy: 'https://restarters.test'
    });

mix.js('resources/global/js/app.js', 'public/global/js')
   .sass('resources/global/css/app.scss', 'public/global/css');

mix.js('resources/wiki/js/wiki.js', 'public/js/wiki.js')
  .sass('resources/wiki/css/app.scss', 'public/css/wiki.css');