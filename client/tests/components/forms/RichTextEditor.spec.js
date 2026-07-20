import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import RichTextEditor from '../../../app/components/forms/RichTextEditor.vue'

// Quill needs real browser layout/range APIs happy-dom doesn't fully
// provide, so it's mocked here the same way uppy is mocked for
// TusImageUpload.spec.js - the component's own responsibility (wiring
// props/emits to Quill's API) is what's under test, not Quill itself.
let lastInstance = null

class MockQuill {
  constructor(el, options) {
    this.el = el
    this.options = options
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

  // htmlEditButton registration (Quill.register) is a static no-op here -
  // the module itself is mocked below, so there's nothing real to wire up.
  static register() {}

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
// The "<>" view-HTML-source toolbar button (parity fix) - its own module
// isn't under test here, same rationale as mocking quill itself.
vi.mock('quill-html-edit-button', () => ({ default: {} }))

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

  // Rendered-parity fix: develop's toolbar has a "<>" view-HTML-source
  // button (quill-html-edit-button) after H4/H5/H6 that this component was
  // missing entirely.
  it('registers the htmlEditButton module and enables it in the Quill config', async () => {
    const registerSpy = vi.spyOn(MockQuill, 'register')
    await mountEditor()

    expect(registerSpy).toHaveBeenCalledWith('modules/htmlEditButton', {})
    expect(lastInstance.options.modules.htmlEditButton).toEqual({})
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
