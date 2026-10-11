#!/usr/bin/env node
// Linguistic-complexity scorer for PR prose (stdin -> problems on stdout).
//
// Prints nothing when the text is fine. Three measurements, all computed
// rather than listed, so coined terms and packed sentences fail even when
// nobody predicted them:
//
//   - Flesch-Kincaid reading grade over the whole body: must be <= 13
//   - any single sentence over 40 words: listed
//   - coined hyphen-compounds ("label-admitted", "union-ready"): a made-up
//     adjective is a term of art by construction

// Thresholds. The defaults are the PR ones and are what you get with no env set.
// check-discourse-post.sh tightens both, because a reply to a volunteer is read once,
// on a phone, by someone who does not work on the code.
const MAX_GRADE = Number(process.env.PROSE_MAX_GRADE || 13)
const MAX_SENTENCE_WORDS = Number(process.env.PROSE_MAX_SENTENCE_WORDS || 40)

const WORD = /[A-Za-z][A-Za-z'-]*/g

function wordsOf(s) {
  return s.match(WORD) || []
}

function wordCount(s) {
  return s.split(/\s+/).filter(Boolean).length
}

function syllables(w) {
  w = w.toLowerCase().replace(/^['"]+/, '').replace(/['"]+$/, '')
  if (!w) return 0
  let groups = (w.match(/[aeiouy]+/g) || []).length
  if (w.endsWith('e') && !/(?:le|ee|ye)$/.test(w) && groups > 1) groups -= 1
  return Math.max(1, groups)
}

function grade(words) {
  if (!words.length) return 0
  const asw = words.reduce((n, w) => n + syllables(w), 0) / words.length
  return 0.39 * words.length + 11.8 * asw - 15.59
}

// Whole-body grade uses average sentence length, so it takes the sentence
// count; the per-sentence figure is the same formula with one sentence.
function bodyGrade(words, sentenceCount) {
  const asl = words.length / sentenceCount
  const asw = words.reduce((n, w) => n + syllables(w), 0) / words.length
  return 0.39 * asl + 11.8 * asw - 15.59
}

// A first element that is an ordinary modifier, not half of a coinage.
const ALLOWED_FIRST = new Set([
  're', 'dry', 'one', 'two', 'three', 'four', 'self', 'well', 'half', 'non',
  'per', 'opt', 'follow', 'built', 'read', 'day', 'week', 'hour', 'minute',
  'long', 'short', 'full', 'end', 'first', 'second', 'third', 'co', 'pre',
  'post', 'mid', 'anti', 'all', 'so', 'up', 'down', 'off', 'on', 'over',
  'under', 'out', 'in', 'e', 'cross', 'road', 'drive', 'travel', 'new',
  'old', 'high', 'low', 'best', 'worst', 'real', 'top', 'left', 'right',
  'hand', 'man', 'double', 'single', 'open', 'close', 'far',
])

function main(raw) {
  // Markdown structure markers are not prose. Neither is a URL: its host and path
  // segments would be counted as words and syllables, so a link inflates the reading
  // grade of the sentence carrying it, and a hyphenated slug reads as a coined
  // compound. House style asks for links in this text, so they have to be free.
  const text = raw
    .replace(/https?:\/\/[^\s<>)"\]]*/g, '')
    .replace(/^[#>*-]+\s*/gm, '')
    .replace(/\*\*|__|\*/g, '')

  const sentences = text
    .split(/(?<=[.!?:])\s+/)
    .map((s) => s.trim())
    .filter((s) => wordCount(s) >= 4)
  if (!sentences.length) return

  const words = sentences.flatMap(wordsOf)
  if (!words.length) return

  const problems = []
  const overall = bodyGrade(words, sentences.length)
  if (overall > MAX_GRADE) {
    const worst = [...sentences]
      .sort((a, b) => grade(wordsOf(b)) - grade(wordsOf(a)))
      .slice(0, 4)
    problems.push(`OVERALL reading grade ${overall.toFixed(1)} (max ${MAX_GRADE}). Hardest sentences:`)
    for (const s of worst) {
      problems.push(
        `  [grade ${grade(wordsOf(s)).toFixed(0)}, ${wordCount(s)} words] ${s.split(/\s+/).join(' ').slice(0, 150)}`
      )
    }
  }

  const longSents = sentences.filter((s) => wordCount(s) > MAX_SENTENCE_WORDS)
  if (longSents.length) {
    problems.push(`SENTENCES over ${MAX_SENTENCE_WORDS} words - split each into claims:`)
    for (const s of longSents.slice(0, 4)) {
      problems.push(`  [${wordCount(s)} words] ${s.split(/\s+/).join(' ').slice(0, 150)}`)
    }
  }

  const compounds = new Set()
  const pat = /\b([a-z]+)-([a-z]+(?:ed|ing|en|ready|only|first|aware|truth|side|level|wide|free|proof))\b/g
  for (const m of text.matchAll(pat)) {
    if (!ALLOWED_FIRST.has(m[1])) compounds.add(m[0])
  }
  if (compounds.size) {
    problems.push('COINED compounds - each is a term of art; say it in words:')
    for (const c of [...compounds].sort().slice(0, 8)) problems.push('  ' + c)
  }

  if (problems.length) console.log(problems.join('\n'))
}

let buf = ''
process.stdin.setEncoding('utf8')
process.stdin.on('data', (d) => { buf += d })
process.stdin.on('end', () => main(buf))
