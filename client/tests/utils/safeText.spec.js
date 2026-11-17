import { describe, expect, it } from 'vitest'
import { isEmailLike, stripTags, trimTrailing } from '../../app/utils/safeText.js'

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const TAG_RE = /<[^>]*>/g

describe('stripTags', () => {
  it.each([
    ['<p>Hello <b>world</b></p>', 'Hello world'],
    ['plain', 'plain'],
    ['', ''],
    ['a < b', 'a < b'],
    ['a <b', 'a <b'],
    ['<a<b>c', 'c'],
    ['x<>y', 'xy'],
    ['<p>one</p>\n<p>two\nlines</p>', 'one\ntwo\nlines'],
    ['<div class="a>b">t</div>', 'b">t'],
    ['1 < 2 and 3 > 2', '1  2'],
    ['<a>1<b', '1<b'],
  ])('matches the old regex on %j', (input, expected) => {
    expect(stripTags(input)).toBe(expected)
    expect(input.replace(TAG_RE, '')).toBe(expected)
  })
  it('stringifies non-strings like String(value)', () => {
    expect(stripTags(12)).toBe('12')
  })
  it('stays fast on pathological input', () => {
    const start = Date.now()
    expect(stripTags('<'.repeat(200000))).toBe('<'.repeat(200000))
    expect(Date.now() - start).toBeLessThan(1000)
  })
})

describe('trimTrailing', () => {
  it.each([
    ['http://x.com/a).', '.,;:!?)', 'http://x.com/a'],
    ['http://x.com', '.,;:!?)', 'http://x.com'],
    ['...', '.,;:!?)', ''],
    ['', '.,;:!?)', ''],
    ['a.b,c', '.,;:!?)', 'a.b,c'],
    ['a,,,', ',', 'a'],
    [',a,', ',', ',a'],
  ])('trims %j of [%s]', (input, chars, expected) => {
    expect(trimTrailing(input, chars)).toBe(expected)
  })
  it('matches the old regexes', () => {
    for (const s of ['x.', 'x).', '.x', 'x,;', 'a b!?']) {
      expect(trimTrailing(s, '.,;:!?)')).toBe(s.replace(/[.,;:!?)]+$/, ''))
    }
    expect(trimTrailing('a,,', ',')).toBe('a,,'.replace(/,+$/, ''))
  })
  it('stays fast on a long run that is not at the end', () => {
    const start = Date.now()
    trimTrailing(','.repeat(200000) + 'x', ',')
    expect(Date.now() - start).toBeLessThan(1000)
  })
})

describe('isEmailLike', () => {
  const cases = [
    'a@b.co',
    'a@b.c',
    'a@.b',
    'a@b.',
    'a@b',
    '@b.co',
    'a@@b.co',
    'a@b@c.co',
    'a b@c.co',
    'a@b .co',
    'a@b.c.d',
    'a@b..c',
    'a@..',
    'a@...',
    'a@.a.',
    'a.b@c.de',
    'first.last+tag@sub.example.org',
    '',
    'plain',
    'a@b.c\n',
    ' a@b.c',
    'a@b .c',
  ]
  it.each(cases)('agrees with the old regex on %j', (input) => {
    expect(isEmailLike(input)).toBe(EMAIL_RE.test(input))
  })
  it('accepts and rejects the obvious cases', () => {
    expect(isEmailLike('jane@bloggs.net')).toBe(true)
    expect(isEmailLike('jane@bloggs')).toBe(false)
  })
  it('stays fast on pathological input', () => {
    const start = Date.now()
    expect(isEmailLike('a@' + '.'.repeat(200000))).toBe(true)
    expect(isEmailLike('a@' + 'b'.repeat(200000))).toBe(false)
    expect(Date.now() - start).toBeLessThan(1000)
  })
})
