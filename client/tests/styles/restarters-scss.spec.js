// Compiles the brand stylesheet directly with Dart Sass, independent of
// Nuxt/Vite's build pipeline, so a broken partial (bad @import, missing
// variable, BS4-only mixin signature, etc.) fails fast in `vitest run`
// rather than only surfacing in a full `nuxi build`.
import path from 'node:path'
import * as sass from 'sass'
import { describe, expect, it } from 'vitest'

// vitest.config.ts runs with client/ as the working directory.
const clientRoot = process.cwd()
const entry = path.join(clientRoot, 'app/assets/css/restarters.scss')

describe('restarters.scss', () => {
  it('compiles with Dart Sass and emits CSS', () => {
    const result = sass.compile(entry, {
      loadPaths: [
        path.join(clientRoot, 'node_modules'),
        path.join(clientRoot, 'app/assets/css'),
      ],
      quietDeps: true,
    })

    expect(result.css.length).toBeGreaterThan(1000)
  })

  it('pins $blue to Bootstrap 4s default rather than Bootstrap 5s', () => {
    const result = sass.compile(entry, {
      loadPaths: [
        path.join(clientRoot, 'node_modules'),
        path.join(clientRoot, 'app/assets/css'),
      ],
      quietDeps: true,
    })

    // #navigation a's colour is the one call site that renders raw $blue
    // untouched by any brand override — asserting on it is a cheap proxy
    // for "the whole $blue-pin-before-Bootstrap trick still works".
    expect(result.css).toContain('#007bff')
    expect(result.css).not.toContain('#0d6efd')
  })

  it('carries the brand primary colour into Bootstrap-generated rules', () => {
    const result = sass.compile(entry, {
      loadPaths: [
        path.join(clientRoot, 'node_modules'),
        path.join(clientRoot, 'app/assets/css'),
      ],
      quietDeps: true,
    })

    expect(result.css).toContain('#0394a6')
  })
})
