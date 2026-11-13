// Linear-time replacements for a few regexes that SonarCloud flags as
// super-linear (S8786). Each one is behaviourally identical to the pattern it
// replaced, which is noted beside it, and pinned in tests/utils/safeText.spec.js.

// Same as html.replace(/<[^>]*>/g, ''): drops everything from a '<' up to the
// next '>' (a '<' inside the tag is swallowed too); an unclosed '<' stays.
export function stripTags(html) {
  const text = String(html)
  let out = ''
  let pos = 0
  while (pos < text.length) {
    const open = text.indexOf('<', pos)
    if (open === -1) break
    const close = text.indexOf('>', open + 1)
    if (close === -1) break
    out += text.slice(pos, open)
    pos = close + 1
  }
  return out + text.slice(pos)
}

// Same as str.replace(new RegExp(`[${chars}]+$`), ''): removes any run of the
// given characters from the end of the string.
export function trimTrailing(str, chars) {
  const text = String(str)
  let end = text.length
  while (end > 0 && chars.includes(text[end - 1])) end--
  return text.slice(0, end)
}

// Same as /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value): no whitespace, exactly one
// '@' with something before it, and a '.' in the domain that has at least one
// character on each side.
export function isEmailLike(value) {
  const text = String(value)
  if (/\s/.test(text)) return false
  const at = text.indexOf('@')
  if (at < 1 || text.indexOf('@', at + 1) !== -1) return false
  const domain = text.slice(at + 1)
  for (let i = 1; i < domain.length - 1; i++) {
    if (domain[i] === '.') return true
  }
  return false
}
