<?php

use Stevebauman\Purify\Definitions\Html5Definition;

/*
|--------------------------------------------------------------------------
| HTML sanitisation for user-authored rich text
|--------------------------------------------------------------------------
|
| Group and event descriptions are Quill-authored HTML that we render unescaped, so
| they are sanitised on write (see Group::setFreeTextAttribute and friends).
|
| The allowlist below is deliberately WIDER than the package default, because it was
| derived from what the live content actually contains rather than from a guess. A
| survey of the production data found ~5,100 links with target=, ~4,500 with rel=,
| ~7,100 style attributes, ~1,300 class attributes, plus div, hr, sub, sup, font and a
| handful of tables - all of which the default allowlist would have silently stripped
| the next time a host edited their group. It also found zero instances of <script>,
| <iframe>, <embed>, <object>, <form>, javascript: URLs or on* handlers, so nothing
| legitimate depends on those.
|
| What keeps this safe is not the element list but HTMLPurifier's model: on* handlers
| are never allowed at all, URIs are restricted to the schemes in URI.AllowedSchemes
| (so javascript: cannot survive in href or src), and style attributes are parsed and
| filtered down to CSS.AllowedProperties, so expression() and url(javascript:) go too.
| HTML.TargetNoopener additionally forces rel="noopener" onto target="_blank" links.
|
| If you widen this, add elements and attributes - do not add schemes or relax
| CSS.AllowedProperties to include anything that can fetch a URL.
|
*/

return [

    'default' => 'default',

    'configs' => [

        'default' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',

            'HTML.AllowedElements' => implode(',', [
                // Text flow and headings.
                'p', 'br', 'span', 'div', 'hr', 'pre', 'blockquote',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                // Inline formatting, all of which appears in the live content.
                'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'sub', 'sup', 'small', 'big', 'font',
                // Lists.
                'ul', 'ol', 'li',
                // Links and images.
                'a', 'img',
                // A few groups use tables for opening hours and the like.
                'table', 'thead', 'tbody', 'tr', 'td', 'th', 'caption',
            ]),

            'HTML.AllowedAttributes' => implode(',', [
                // Quill uses class for alignment/indent; style is used throughout.
                '*.class', '*.style', '*.title', '*.dir', '*.lang',
                'a.href', 'a.target', 'a.rel', 'a.name',
                'img.src', 'img.alt', 'img.width', 'img.height',
                'table.border', 'table.summary',
                'td.colspan', 'td.rowspan', 'td.abbr',
                'th.colspan', 'th.rowspan', 'th.abbr', 'th.scope',
                'font.color', 'font.size', 'font.face',
                'ol.start', 'ol.type', 'li.value',
                // Legacy presentational align, which the Transitional doctype rewrites into
                // text-align CSS. Only p@center and table@left appear in the live data, but
                // dropping it silently re-flows those descriptions.
                'p.align', 'div.align', 'table.align', 'td.align', 'th.align', 'tr.align',
                'h1.align', 'h2.align', 'h3.align', 'h4.align', 'h5.align', 'h6.align', 'img.align',
            ]),

            // Restricted to the properties the live content actually uses. Nothing here can
            // reference a URL, and `position` is deliberately absent: absolute positioning
            // inside user content is a UI-redressing vector.
            'CSS.AllowedProperties' => implode(',', [
                'font', 'font-size', 'font-family', 'font-weight', 'font-style', 'font-variant',
                'color', 'background-color',
                'text-align', 'text-decoration', 'text-indent', 'text-transform',
                'line-height', 'letter-spacing', 'white-space', 'vertical-align', 'direction',
                'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
                'padding', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
                'border', 'border-collapse', 'border-color', 'border-style', 'border-width',
                'width', 'height', 'list-style-type', 'list-style-position',
            ]),
            // box-sizing appears in the live data (pasted from elsewhere) but HTMLPurifier
            // has no validator for it, and listing a property it cannot validate is a hard
            // error rather than a no-op. It is cosmetic, so it gets dropped.

            // Links opening in a new tab are common in the live content, so allow the
            // target values that make sense and let HTMLPurifier add rel="noopener".
            'Attr.AllowedFrameTargets' => '_blank,_self',
            'HTML.TargetNoopener' => true,
            'HTML.TargetNoreferrer' => false,

            // Leave authored markup alone; we are sanitising, not reformatting.
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ],

    ],

    'definitions' => Html5Definition::class,

    'css-definitions' => null,

    'serializer' => [
        // Laravel's cache rather than a serializer directory, so nothing on disk has to
        // be writable in CI or on the Fly volumes.
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],

];
