import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import StatsShareImage from '../../../app/components/events/StatsShareImage.vue'
import en from '../../../i18n/locales/en.json'

// StatsShareImage.vue paints onto a real <canvas> 2D context, which the
// happy-dom test environment doesn't implement (HTMLCanvasElement.
// getContext('2d') returns null there) - so this suite stubs both the 2D
// context (recording the calls the component makes) and window.Image
// (firing onload synchronously instead of actually loading a network image)
// to exercise the component's own drawing logic end-to-end. This checks
// that paint() calls the canvas API with the values
// composables/useStatsShareImage.js's lookup table and sizing math produce
// (background filename, font strings, translated text) - it does NOT
// verify the resulting pixels match develop, which needs a real browser.
// See the PR description for what remains visually unverified.
function makeFakeContext() {
  const fontsSet = []
  const ctx = {
    fillText: vi.fn(),
    measureText: vi.fn((str) => ({
      width: String(str).length * 8,
      actualBoundingBoxAscent: 12,
      actualBoundingBoxDescent: 4,
    })),
    drawImage: vi.fn(),
    fill: vi.fn(),
    rect: vi.fn(),
    beginPath: vi.fn(),
    fillStyle: null,
    strokeStyle: null,
    fontsSet,
  }
  // paint() sets ctx.font twice per paint (once for the main headline, once
  // for the smaller "X seedlings"/"X hectares" line) - record every value
  // set rather than just the last one so tests can check the main size.
  Object.defineProperty(ctx, 'font', {
    get: () => fontsSet[fontsSet.length - 1],
    set: (value) => {
      fontsSet.push(value)
    },
  })
  return ctx
}

let fakeCtx
let getContextSpy
let originalImage

beforeEach(() => {
  vi.useFakeTimers()
  fakeCtx = makeFakeContext()
  getContextSpy = vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(fakeCtx)

  originalImage = globalThis.Image
  class FakeImage {
    set src(value) {
      this._src = value
      // Fire onload on the next tick, like a real (instant, cached) image
      // load would - keeps paint()'s `await insertImage(...)` resolving
      // without needing a real network fetch.
      Promise.resolve().then(() => this.onload?.())
    }

    get src() {
      return this._src
    }
  }
  vi.stubGlobal('Image', FakeImage)
})

afterEach(() => {
  getContextSpy.mockRestore()
  vi.unstubAllGlobals()
  globalThis.Image = originalImage
  vi.useRealTimers()
})

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  return mount(StatsShareImage, {
    props: { count: 600, target: 'Facebook', ...props },
    global: { plugins: [i18n] },
  })
}

// Runs the fake-timer/microtask dance paint() needs: each insertImage()
// resolves 500ms after its Image's onload microtask fires, and paint()
// itself waits another 2000ms before clearing the painting flag.
async function flushPaint() {
  for (let i = 0; i < 10; i++) {
    await vi.advanceTimersByTimeAsync(500)
  }
}

describe('components/events/StatsShareImage', () => {
  it('draws the background image the CO2e->image lookup table selects for the given count/target', async () => {
    const wrapper = mountComponent({ count: 600, target: 'Facebook' })
    await flushPaint()

    // 600kg, landscape (Facebook) -> level 2 increment 10 (see
    // useStatsShareImage.spec.js's equivalent lookup-table assertion).
    expect(fakeCtx.drawImage).toHaveBeenCalled()
    const firstImg = fakeCtx.drawImage.mock.calls[0][0]
    expect(firstImg.src).toContain('/images/stats/ImpactRange2Landscape-10.png')

    wrapper.unmount()
  })

  it('draws the Square background variant for the portrait (Instagram) target', async () => {
    const wrapper = mountComponent({ count: 600, target: 'Instagram' })
    await flushPaint()

    const firstImg = fakeCtx.drawImage.mock.calls[0][0]
    expect(firstImg.src).toContain('/images/stats/ImpactRange2Square-10.png')

    wrapper.unmount()
  })

  it('writes the translated "we\'ve saved"/"of CO2e" headline text', async () => {
    const wrapper = mountComponent({ count: 600, target: 'Facebook' })
    await flushPaint()

    const written = fakeCtx.fillText.mock.calls.map((call) => call[0]).join(' | ')
    expect(written).toContain("We've saved")
    expect(written).toContain('of CO2e')
    expect(written).toContain('600 kg')

    wrapper.unmount()
  })

  it('sets a bold Asap font string sized by useStatsShareImage\'s per-platform/locale fontSize()', async () => {
    const wrapper = mountComponent({ count: 600, target: 'Twitter' })
    await flushPaint()

    // English/Twitter -> 60px per useStatsShareImage.spec.js (the headline
    // font is set first; the smaller "seedlings" line font follows it).
    expect(fakeCtx.fontsSet[0]).toContain('60px')
    expect(fakeCtx.fontsSet[0]).toContain('Asap')

    wrapper.unmount()
  })

  it('emits update:painting true then false around a paint cycle', async () => {
    const wrapper = mountComponent()
    await flushPaint()

    const events = wrapper.emitted('update:painting')
    expect(events[0]).toEqual([true])
    expect(events[events.length - 1]).toEqual([false])

    wrapper.unmount()
  })

  it('repaints when the target prop changes', async () => {
    const wrapper = mountComponent({ count: 600, target: 'Facebook' })
    await flushPaint()
    fakeCtx.drawImage.mockClear()

    await wrapper.setProps({ target: 'Instagram' })
    await flushPaint()

    const firstImg = fakeCtx.drawImage.mock.calls[0][0]
    expect(firstImg.src).toContain('Square')

    wrapper.unmount()
  })

  it('shows the "This image is WxH pixels" caption when size is set', async () => {
    const wrapper = mountComponent({ target: 'Facebook', size: true })
    await flushPaint()
    expect(wrapper.text()).toContain('1200x630')
    wrapper.unmount()
  })

  it('exposes download(), which builds a link from canvas.toDataURL() and clicks it', async () => {
    const wrapper = mountComponent()
    await flushPaint()

    const canvasEl = wrapper.find('canvas').element
    canvasEl.toDataURL = vi.fn(() => 'data:image/png;base64,fake')

    const clickSpy = vi.fn()
    const anchor = { click: clickSpy, download: null, href: null }
    const realCreateElement = document.createElement.bind(document)
    const createElementSpy = vi
      .spyOn(document, 'createElement')
      .mockImplementation((tag) => (tag === 'a' ? anchor : realCreateElement(tag)))

    wrapper.vm.download()

    expect(clickSpy).toHaveBeenCalled()
    expect(anchor.download).toBe('stats.png')
    expect(anchor.href).toBe('data:image/png;base64,fake')

    createElementSpy.mockRestore()
    wrapper.unmount()
  })
})
