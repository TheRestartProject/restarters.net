import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventImagesGallery from '../../../app/components/events/EventImagesGallery.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue" data-testid="stub-modal"><slot /><slot name="footer" /></div>',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountGallery(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventImagesGallery, {
    props: { images: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BButton: BButtonStub },
    },
  })
}

// Ports EventImages.vue/EventImage.vue/EventImageModal.vue (gap 5) - a
// read-only thumbnail gallery + click-to-zoom lightbox, unblocked now GET
// /api/v2/events/{id} returns `images` ({id, idxref, path} per
// App\Http\Resources\Image). No delete affordance - confirmed against
// develop's source that the view page never had one (it lives on the
// separate edit-page upload dropzone instead).
describe('components/events/EventImagesGallery', () => {
  it('renders nothing when there are no images', () => {
    const wrapper = mountGallery({ images: [] })
    expect(wrapper.find('[data-testid="event-images-gallery"]').exists()).toBe(false)
  })

  it('renders a thumbnail per image, using the thumbnail_-prefixed filename', () => {
    const wrapper = mountGallery({
      images: [
        { id: 1, idxref: 101, path: 'abc.jpg' },
        { id: 2, idxref: 102, path: 'def.jpg' },
      ],
    })

    const thumbs = wrapper.findAll('[data-testid="event-image-thumb"]')
    expect(thumbs).toHaveLength(2)
    expect(thumbs[0].attributes('src')).toContain('/uploads/thumbnail_abc.jpg')
    expect(thumbs[1].attributes('src')).toContain('/uploads/thumbnail_def.jpg')
  })

  it('shows the count in the section title', () => {
    const wrapper = mountGallery({ images: [{ id: 1, idxref: 101, path: 'abc.jpg' }] })
    expect(wrapper.find('[data-testid="event-images-gallery"]').text()).toContain('1')
  })

  it('opens a lightbox with the full-size (non-thumbnail) image on thumbnail click, and closes it', async () => {
    const wrapper = mountGallery({ images: [{ id: 1, idxref: 101, path: 'abc.jpg' }] })

    expect(wrapper.find('[data-testid="event-image-modal"]').exists()).toBe(false)

    await wrapper.find('[data-testid="event-image-thumb"]').trigger('click')

    const modal = wrapper.find('[data-testid="event-image-modal"]')
    expect(modal.exists()).toBe(true)
    const fullImg = modal.find('img')
    expect(fullImg.attributes('src')).toContain('/uploads/abc.jpg')
    expect(fullImg.attributes('src')).not.toContain('thumbnail_')

    await wrapper.find('[data-testid="event-image-modal-close"]').trigger('click')
    expect(wrapper.find('[data-testid="event-image-modal"]').exists()).toBe(false)
  })
})
