import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import RichTextEditor from '../../../app/components/forms/RichTextEditor.vue'

// Quill needs real browser layout/range APIs happy-dom doesn't fully
// provide, so it's mocked here the same way uppy is mocked for
// TusImageUpload.spec.js - the component's own responsibility (wiring
// props/emits to Quill's API) is what's under test, not Quill itself.
let lastInstance = null

class MockQuill {
  constructor(el) {
    this.el = el
    this.root = document.createElement('div')
    this.root.className = 'ql-editor'
    this.handlers = {}
    lastInstance = this
    this.clipboard = {
      dangerouslyPasteHTML: (html) => {
        this.root.innerHTML = html
      },
    }
  }

  on(event, handler) {
    this.handlers[event] = handler
  }

  setText() {
    this.root.innerHTML = ''
  }

  emitChange(html) {
    this.root.innerHTML = html
    this.handlers['text-change']?.()
  }
}

vi.mock('quill', () => ({ default: MockQuill }))

async function mountEditor(props = {}) {
  const wrapper = mount(RichTextEditor, { props })
  await flushMounted()
  return wrapper
}

async function flushMounted() {
  // Two microtask/tick rounds: one for the dynamic import() to resolve,
  // one for Vue to process the state it sets afterwards.
  await Promise.resolve()
  await Promise.resolve()
  await new Promise((resolve) => setTimeout(resolve, 0))
}

describe('components/forms/RichTextEditor', () => {
  it('renders a mount point with the has-error class hook wired', async () => {
    const wrapper = await mountEditor({ hasError: true, testid: 'my-editor' })
    expect(wrapper.find('[data-testid="my-editor"]').classes()).toContain('has-error')
  })

  it('pastes the initial modelValue into Quill on mount', async () => {
    await mountEditor({ modelValue: '<p>Hello</p>' })
    expect(lastInstance.root.innerHTML).toBe('<p>Hello</p>')
  })

  it('emits update:modelValue when Quill reports a text-change', async () => {
    const wrapper = await mountEditor({ modelValue: '' })
    lastInstance.emitChange('<p>New text</p>')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['<p>New text</p>'])
  })

  it('emits an empty string instead of the empty-paragraph artifact', async () => {
    const wrapper = await mountEditor({ modelValue: '' })
    lastInstance.emitChange('<p><br></p>')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual([''])
  })
})
