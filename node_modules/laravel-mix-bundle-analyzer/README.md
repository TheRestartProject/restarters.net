# Laravel Mix Bundle Analyzer

This extension adds webpack bundle analyzer support to laravel mix

## Instalation

```
npm install laravel-mix-bundle-analyzer --save-dev
```

## Requirements

`laravel-mix >= 2.1`

## Usage

Your `webpack.mix.js` should look like this:

```js
const mix = require('laravel-mix');
require('laravel-mix-bundle-analyzer');

if (!mix.inProduction()) {
    mix.bundleAnalyzer();
}
```

You now can run `npm run dev`.

You might only want to open the analyzer when watching:

```js
const mix = require('laravel-mix');
require('laravel-mix-bundle-analyzer');

if (mix.isWatching()) {
    mix.bundleAnalyzer();
}
```

Therefor you must run `npm run watch` or `npm run watch-poll`.

## Options

You can choose not to open a window by default:

```js
mix.bundleAnalyzer({
    openAnalyzer: false,
});
```

All options:

|Name|Type|Description|
|:--:|:--:|:----------|
|**`analyzerMode`**|One of: `server`, `static`, `disabled`|Default: `server`. In `server` mode analyzer will start HTTP server to show bundle report. In `static` mode single HTML file with bundle report will be generated. In `disabled` mode you can use this plugin to just generate Webpack Stats JSON file by setting `generateStatsFile` to `true`. |
|**`analyzerHost`**|`{String}`|Default: `127.0.0.1`. Host that will be used in `server` mode to start HTTP server.|
|**`analyzerPort`**|`{Number}`|Default: `8888`. Port that will be used in `server` mode to start HTTP server.|
|**`reportFilename`**|`{String}`|Default: `report.html`. Path to bundle report file that will be generated in `static` mode. It can be either an absolute path or a path relative to a bundle output directory (which is output.path in webpack config).|
|**`defaultSizes`**|One of: `stat`, `parsed`, `gzip`|Default: `parsed`. Module sizes to show in report by default. [Size definitions](#size-definitions) section describes what these values mean.|
|**`openAnalyzer`**|`{Boolean}`|Default: `true`. Automatically open report in default browser.|
|**`generateStatsFile`**|`{Boolean}`|Default: `false`. If `true`, webpack stats JSON file will be generated in bundle output directory|
|**`statsFilename`**|`{String}`|Default: `stats.json`. Name of webpack stats JSON file that will be generated if `generateStatsFile` is `true`. It can be either an absolute path or a path relative to a bundle output directory (which is output.path in webpack config).|
|**`statsOptions`**|`null` or `{Object}`|Default: `null`. Options for `stats.toJson()` method. For example you can exclude sources of your modules from stats file with `source: false` option. [See more options here](https://webpack.js.org/configuration/stats/). |
|**`excludeAssets`**|`{null\|pattern\|pattern[]}` where `pattern` equals to `{String\|RegExp\|function}`|Default: `null`. Patterns that will be used to match against asset names to exclude them from the report. If pattern is a string it will be converted to RegExp via `new RegExp(str)`. If pattern is a function it should have the following signature `(assetName: string) => boolean` and should return `true` to *exclude* matching asset. If multiple patterns are provided asset should match at least one of them to be excluded. |
|**`logLevel`**|One of: `info`, `warn`, `error`, `silent`|Default: `info`. Used to control how much details the plugin outputs.|

## Author

Maxim Vanhove
Web developer at [Starring Jane](https://starringjane.com)

 [![Twitter Follow](https://img.shields.io/twitter/follow/MrMaximVanhove.svg?style=social&logo=twitter&label=Follow)](https://twitter.com/MrMaximVanhove)
