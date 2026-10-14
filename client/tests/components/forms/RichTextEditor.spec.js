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
      matchers: [],
      dangerouslyPasteHTML: (html) => {
        this.root.innerHTML = html
      },
      addMatcher: (selector, matcher) => {
        this.clipboard.matchers.push([selector, matcher])
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

  // legacy's quill-paste-smart clipboard allowlist (allowed.tags/attributes,
  // substituteBlockElements, magicPasteLinks) has no Quill-2 build, so the
  // same allowlist is reproduced with Quill 2's own clipboard.addMatcher
  // hooks instead - registered once per mount.
  describe('paste sanitization (develop\'s quill-paste-smart allowlist)', () => {
    function matcherFor(selector) {
      const found = lastInstance.clipboard.matchers.find(([s]) => s === selector)
      return found?.[1]
    }

    it('registers a matcher for every element, for h1-h3, and for text nodes', async () => {
      await mountEditor()
      expect(matcherFor(Node.ELEMENT_NODE)).toBeTypeOf('function')
      expect(matcherFor('h1, h2, h3')).toBeTypeOf('function')
      expect(matcherFor(Node.TEXT_NODE)).toBeTypeOf('function')
    })

    it('strips colour/background/font/size/align/direction from pasted formatting, outside develop\'s allowed.attributes', async () => {
      await mountEditor()
      const sanitize = matcherFor(Node.ELEMENT_NODE)
      const delta = {
        ops: [
          { insert: 'styled', attributes: { color: 'red', bold: true, background: 'yellow', font: 'serif', size: 'large', align: 'center', direction: 'rtl' } },
        ],
      }

      const result = sanitize(document.createElement('span'), delta)

      expect(result.ops[0].attributes).toEqual({ bold: true })
    })

    it('drops the attributes key entirely once nothing allowed is left', async () => {
      await mountEditor()
      const sanitize = matcherFor(Node.ELEMENT_NODE)
      const delta = { ops: [{ insert: 'x', attributes: { color: 'red' } }] }

      const result = sanitize(document.createElement('span'), delta)

      expect(result.ops[0].attributes).toBeUndefined()
    })

    // Security-relevant: develop's quill-paste-smart allowed.tags is
    // ['a','b','strong','u','s','i','p','br','ul','ol','li','span','h4',
    // 'h5','h6'] (node_modules/quill-paste-smart/dist/quill-paste-smart.js
    // feeds this straight into DOMPurify's ALLOWED_TAGS) - img is not on
    // that list, so a pasted <img> must not survive even though it's an
    // element Quill's default clipboard would otherwise convert to an
    // image embed.
    it('strips a pasted <img> (outside develop\'s allowed.tags) but keeps an allowed <strong>', async () => {
      await mountEditor()
      const sanitize = matcherFor(Node.ELEMENT_NODE)

      const imgDelta = { ops: [{ insert: { image: 'https://evil.example/x.png' } }] }
      const imgResult = sanitize(document.createElement('img'), imgDelta)
      expect(imgResult.ops).toEqual([])

      const strongDelta = { ops: [{ insert: 'bold text', attributes: { bold: true } }] }
      const strongResult = sanitize(document.createElement('strong'), strongDelta)
      expect(strongResult.ops).toEqual([{ insert: 'bold text', attributes: { bold: true } }])
    })

    it('downgrades pasted h1-h3 headings to plain paragraphs, outside develop\'s h4-h6-only allowed.tags', async () => {
      await mountEditor()
      const downgrade = matcherFor('h1, h2, h3')
      const delta = { ops: [{ insert: 'Big heading', attributes: { header: 1 } }] }

      const result = downgrade(document.createElement('h1'), delta)

      expect(result.ops[0].attributes).toBeUndefined()
    })

    it('auto-links a bare URL pasted as plain text (magicPasteLinks)', async () => {
      await mountEditor()
      const magicLinks = matcherFor(Node.TEXT_NODE)
      const textNode = document.createTextNode('see https://example.com/page for details')
      const delta = { ops: [{ insert: 'see https://example.com/page for details' }] }

      const result = magicLinks(textNode, delta)

      const linkOp = result.ops.find((op) => op.attributes?.link)
      expect(linkOp).toEqual({ insert: 'https://example.com/page', attributes: { link: 'https://example.com/page' } })
    })

    it('does not re-linkify text already inside an anchor', async () => {
      await mountEditor()
      const magicLinks = matcherFor(Node.TEXT_NODE)
      const anchor = document.createElement('a')
      anchor.href = 'https://example.com'
      const textNode = document.createTextNode('https://example.com')
      anchor.appendChild(textNode)
      const delta = { ops: [{ insert: 'https://example.com' }] }

      const result = magicLinks(textNode, delta)

      expect(result).toBe(delta)
      expect(result.ops[0].attributes).toBeUndefined()
    })
  })
})
