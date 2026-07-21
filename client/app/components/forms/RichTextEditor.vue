<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import 'quill/dist/quill.snow.css'

// Thin Quill 2 wrapper (design.md §6.2 B6 task brief: "Quill 2 thin
// wrapper, .ql-editor class preserved for e2e parity" - resources/js/
// components/RichTextEditor.vue is the functional spec, but that one wraps
// Quill 1 via vue2-editor, which has no Vue 3 build). Quill itself creates
// the `.ql-editor`/`.ql-container`/`.ql-toolbar` classes when it mounts, so
// no extra markup is needed to preserve them for e2e selectors. The snow
// theme's own CSS import above is required, not cosmetic: without it the
// toolbar buttons render as a row of unstyled boxes and Quill's internal
// `.ql-clipboard` paste-staging div (normally hidden off-screen by this
// stylesheet) shows up as a stray empty text input in the middle of the
// page (parity-shots/desktop/15-event-create__new.png).
//
// Rendered-parity fix: htmlEditButton (the toolbar's "<>" view-HTML-source
// button, quill-html-edit-button@3.x's default `buttonHTML: '<>'`) DOES
// have a Quill 2 build now (peerDependency quill: '^2.x', matching the
// quill@2.0.3 installed here) - registered below the same way legacy
// registered the Quill-1 build, `htmlEditButton: {}` in modules (it
// self-injects its own toolbar button; no separate `toolbar` array entry
// needed, same as legacy's config).
//
// Paste sanitization: legacy registers quill-paste-smart with an explicit
// allowlist (allowed.tags/attributes, substituteBlockElements,
// magicPasteLinks - see below). quill-paste-smart itself has no Quill-2
// build, and adding a replacement package is outside this component's
// scope, so the same allowlist is reproduced with Quill 2's own
// `clipboard.addMatcher` hooks below instead of pulling in a dependency.
const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  hasError: {
    type: Boolean,
    default: false,
  },
  testid: {
    type: String,
    default: 'rich-text-editor',
  },
})

const emit = defineEmits(['update:modelValue'])

const editorEl = ref(null)
let quill = null
let applyingExternalValue = false

function normalize(html) {
  // Same fixup the legacy editor applies for the Linux <p><br> artifact.
  return html ? html.replace('<p><br></p>', '') : html
}

// legacy's quill-paste-smart allowed.tags is ['a','b','strong','u','s','i',
// 'p','br','ul','ol','li','span','h4','h5','h6'] - everything else that
// Quill 2's own default clipboard matcher would otherwise carry across
// (colour, background, font, size, alignment, text direction) is outside
// that list and gets dropped here.
const DISALLOWED_PASTE_FORMATS = ['align', 'background', 'color', 'direction', 'font', 'size']

function stripAttributes(delta, names) {
  delta.ops.forEach((op) => {
    if (!op.attributes) return
    const kept = Object.fromEntries(Object.entries(op.attributes).filter(([key]) => !names.includes(key)))
    if (Object.keys(kept).length === 0) {
      delete op.attributes
    } else {
      op.attributes = kept
    }
  })
  return delta
}

// Node.ELEMENT_NODE matches every element the paste converts, mirroring
// allowed.attributes (href/rel/target/class survive - Quill's own Link blot
// always stamps rel="noopener noreferrer" target="_blank" on every link it
// creates, so those need no extra handling here).
function sanitizePastedFormats(_node, delta) {
  return stripAttributes(delta, DISALLOWED_PASTE_FORMATS)
}

// allowed.tags stops at h4/h5/h6 (matching the toolbar's header options),
// so h1-h3 aren't on the allowlist. substituteBlockElements downgrades an
// unlisted block tag to a plain paragraph rather than dropping it - closest
// equivalent here is stripping the header format so the text survives as
// an ordinary paragraph.
function downgradePastedHeadings(_node, delta) {
  return stripAttributes(delta, ['header'])
}

// magicPasteLinks: auto-link bare URLs typed/pasted as plain text (a link
// already carries the `link` attribute via Quill's own <a> handling, so
// this only ever adds formatting Quill's default clipboard wouldn't).
const BARE_URL_PATTERN = /(https?:\/\/\S+|www\.\S+)/gi

function magicPasteLinks(node, delta) {
  if (node.parentElement?.closest('a')) return delta

  delta.ops = delta.ops.reduce((ops, op) => {
    if (typeof op.insert !== 'string' || op.attributes?.link) {
      ops.push(op)
      return ops
    }

    BARE_URL_PATTERN.lastIndex = 0
    let lastIndex = 0
    let match
    while ((match = BARE_URL_PATTERN.exec(op.insert))) {
      if (match.index > lastIndex) {
        ops.push({ insert: op.insert.slice(lastIndex, match.index), attributes: op.attributes })
      }
      // Trailing punctuation (sentence-ending '.', ')', etc.) isn't part of
      // the URL.
      const url = match[0].replace(/[.,;:!?)]+$/, '')
      const trailing = match[0].slice(url.length)
      const href = url.startsWith('www.') ? `https://${url}` : url
      ops.push({ insert: url, attributes: { ...op.attributes, link: href } })
      if (trailing) ops.push({ insert: trailing, attributes: op.attributes })
      lastIndex = match.index + match[0].length
    }
    if (lastIndex < op.insert.length) {
      ops.push({ insert: op.insert.slice(lastIndex), attributes: op.attributes })
    }
    return ops
  }, [])

  return delta
}

onMounted(async () => {
  const [{ default: Quill }, { default: htmlEditButton }] = await Promise.all([
    import('quill'),
    import('quill-html-edit-button'),
  ])

  Quill.register('modules/htmlEditButton', htmlEditButton)

  quill = new Quill(editorEl.value, {
    theme: 'snow',
    modules: {
      toolbar: [
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        [{ header: '4' }, { header: '5' }, { header: '6' }],
      ],
      htmlEditButton: {},
    },
  })

  quill.clipboard.addMatcher(Node.ELEMENT_NODE, sanitizePastedFormats)
  quill.clipboard.addMatcher('h1, h2, h3', downgradePastedHeadings)
  quill.clipboard.addMatcher(Node.TEXT_NODE, magicPasteLinks)

  if (props.modelValue) {
    quill.clipboard.dangerouslyPasteHTML(props.modelValue)
  }

  quill.on('text-change', () => {
    if (applyingExternalValue) return
    const html = normalize(quill.root.innerHTML)
    emit('update:modelValue', html === '<p><br></p>' ? '' : html)
  })
})

watch(
  () => props.modelValue,
  (newVal) => {
    if (!quill) return
    const current = normalize(quill.root.innerHTML)
    if (normalize(newVal) === current) return

    applyingExternalValue = true
    quill.setText('')
    if (newVal) {
      quill.clipboard.dangerouslyPasteHTML(newVal)
    }
    applyingExternalValue = false
  },
)

onBeforeUnmount(() => {
  quill = null
})
</script>

<template>
  <div class="rich-text-editor" :class="{ 'has-error': hasError }" :data-testid="testid">
    <div ref="editorEl" />
  </div>
</template>

<style scoped lang="scss">
.rich-text-editor {
  // develop's fixed, scrolling 300px box (not a flexible min-height).
  :deep(.ql-editor),
  :deep(.ql-container) {
    min-height: 300px !important;
    max-height: 300px !important;
    height: 300px !important;
  }

  // develop's in-editor heading sizes for the toolbar's H4/H5/H6 options -
  // otherwise typed/pasted headings all render at the browser's default h4/
  // h5/h6 sizes (or whatever the snow theme's own defaults are).
  :deep(.ql-editor) {
    h4 {
      font-size: 1.5rem;
    }

    h5 {
      font-size: 1.25rem;
    }

    h6 {
      font-size: 1rem;
    }
  }

  &.has-error {
    :deep(.ql-toolbar),
    :deep(.ql-container) {
      border-color: #dc3545 !important;
    }
  }
}
</style>
