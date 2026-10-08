import { readFileSync, globSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

const CLIENT_DIR = join(dirname(fileURLToPath(import.meta.url)), '..')

// Every t('...') call in the client must resolve against the shipped locale
// files. This exists because unresolved keys are not a silent failure - vue-i18n
// renders the KEY ITSELF, so users saw the literal strings
// "groups.export.events.date" as a table header and "partials.delete" on a
// button. Both survived every other check: the components rendered, the tests
// passed, and a screenshot of a header row reads as text either way.
//
// The subtle case worth knowing: develop stores some keys FLAT with literal
// dots ('export.events.date' => 'Date'), which vue-i18n reads as a nested path
// and never finds. This catches that too.
const msgs = JSON.parse(readFileSync(join(CLIENT_DIR, 'i18n/locales/en.json'), 'utf8'))
const client = JSON.parse(readFileSync(join(CLIENT_DIR, 'i18n/locales/client-en.json'), 'utf8'))

function resolve(path, root) {
  let cur = root
  for (const part of path.split('.')) {
    if (cur && typeof cur === 'object' && part in cur) cur = cur[part]
    else return null
  }
  return typeof cur === 'string' ? cur : null
}

describe('i18n keys', () => {
  it('every t() key used in the client resolves', () => {
    const files = globSync('app/**/*.{vue,js}', { cwd: CLIENT_DIR })
    const missing = []

    for (const rel of files) {
      const src = readFileSync(join(CLIENT_DIR, rel), 'utf8')
      for (const m of src.matchAll(/\bt\(\s*'([a-zA-Z0-9_.-]+)'/g)) {
        const key = m[1]
        const found = key.startsWith('client.')
          ? resolve(key.slice('client.'.length), client.client || {})
          : resolve(key, msgs)
        if (found === null) missing.push(`${rel} -> ${key}`)
      }
    }

    expect(missing).toEqual([])
  })
})
