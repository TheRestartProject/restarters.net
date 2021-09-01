const mix = require('laravel-mix');

/**
 * Determine if Mix should watch files for changes.
 */
if (!mix.isWatching) {
    mix.isWatching = () => {
        return (
            process.argv.includes('--watch') || process.argv.includes('--hot')
        );
    };
}
