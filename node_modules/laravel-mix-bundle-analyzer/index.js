const mix = require('laravel-mix');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

/**
 * Add isWatching helper
 */
require('./isWatching');

class BundleAnalyzer {
    register (options = {}) {
        this.options = options;
    }

    dependencies () {
        return ['webpack-bundle-analyzer'];
    }

    webpackPlugins () {
        return new BundleAnalyzerPlugin(this.options);
    }
}

mix.extend('bundleAnalyzer', new BundleAnalyzer());
