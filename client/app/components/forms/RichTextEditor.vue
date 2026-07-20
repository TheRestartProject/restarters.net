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
// needed, same as legacy's config). quill-paste-smart genuinely has no
// Quill-2 build - paste is handled by Quill 2's own (safer) default
// clipboard matcher instead.
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
  :deep(.ql-editor) {
    min-height: 200px;
  }

  &.has-error {
    :deep(.ql-toolbar),
    :deep(.ql-container) {
      border-color: #dc3545 !important;
    }
  }
}
</style>
