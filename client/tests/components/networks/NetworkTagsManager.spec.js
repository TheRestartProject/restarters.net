import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkTagsManager from '../../../app/components/networks/NetworkTagsManager.vue'
import { useNetworksStore } from '../../../app/stores/networks.js'
import { BFormGroupStub, BFormStub, BModalStub } from '../../helpers/stubs.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

// lang/en/networks.php gained `tags.new_tag_placeholder`/
// `tags.description_placeholder` alongside this Nuxt work (parity-v2/
// networks.md gap #4) but client/i18n/locales/en.json is a generated,
// checked-in artifact this change intentionally leaves untouched - overlay
// the new keys here.
function mountComponent(props = {}) {
  const messages = {
    en: {
      ...en,
      ...clientEn,
      networks: {
        ...en.networks,
        tags: {
          ...en.networks.tags,
          new_tag_placeholder: 'New tag name...',
          description_placeholder: 'Description (optional)',
        },
      },
    },
  }
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(NetworkTagsManager, {
    props: { networkId: 1, ...props },
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub, BModal: BModalStub, BForm: BFormStub, BFormGroup: BFormGroupStub },
    },
  })
}

describe('components/networks/NetworkTagsManager', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNetworksStore()
  })

  it('shows the "no tags" message when the network has none', () => {
    store.tags.data = []
    const wrapper = mountComponent()
    expect(wrapper.get('[data-testid="network-tags-empty"]').text()).toBe('No tags created yet.')
  })

  // Legacy NetworkPage.vue's tags-management: bordered card rows with the
  // name + pluralised group count and the description shown INLINE
  // underneath (parity-v2/networks.md gap #4) - AdminCrudTable's plain
  // table never showed description at all.
  it('renders each tag with its name, group count, and inline description', () => {
    store.tags.data = [{ id: 1, name: 'Scotland', description: 'Groups in Scotland', groups_count: 3 }]

    const wrapper = mountComponent()

    const row = wrapper.get('[data-testid="network-tag-1"]')
    expect(row.text()).toContain('Scotland')
    expect(row.text()).toContain('3 Groups')
    expect(wrapper.get('[data-testid="network-tag-description-1"]').text()).toBe('Groups in Scotland')
  })

  it('does not render a description row when the tag has none', () => {
    store.tags.data = [{ id: 1, name: 'Scotland', description: null, groups_count: 0 }]

    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="network-tag-description-1"]').exists()).toBe(false)
  })

  // NetworkPage.vue's edit/delete buttons use plain monochrome
  // pencil.svg/trash.svg (18px/20px), not the coloured edit_ico_green.svg/
  // delete_ico_red.svg icon set (rendered-diff finding #88).
  it('uses develop\'s plain monochrome pencil/trash icons for edit and delete', () => {
    store.tags.data = [{ id: 1, name: 'Scotland', description: null, groups_count: 0 }]

    const wrapper = mountComponent()

    const editIcon = wrapper.get('[data-testid="network-tag-edit-1"] img')
    expect(editIcon.attributes('src')).toBe('/images/pencil.svg')
    expect(editIcon.attributes('width')).toBe('18')

    const deleteIcon = wrapper.get('[data-testid="network-tag-delete-1"] img')
    expect(deleteIcon.attributes('src')).toBe('/images/trash.svg')
    expect(deleteIcon.attributes('width')).toBe('20')
  })

  it('always shows the inline create form (not a modal-behind-a-button)', async () => {
    store.tags.data = []
    store.createTag = vi.fn().mockResolvedValue({ id: 2, name: 'New Tag', description: null, groups_count: 0 })

    const wrapper = mountComponent()

    const form = wrapper.get('[data-testid="network-tags-create-form"]')
    await form.get('[data-testid="network-tags-create-name"]').setValue('New Tag')
    await form.trigger('submit')
    await flushPromises()

    expect(store.createTag).toHaveBeenCalledWith(1, { name: 'New Tag', description: null })
  })

  it('opens the edit modal with the tag pre-filled and saves via updateTag', async () => {
    store.tags.data = [{ id: 1, name: 'Scotland', description: 'Old description', groups_count: 0 }]
    store.updateTag = vi.fn().mockResolvedValue({ id: 1, name: 'Scotland (updated)', description: 'Old description', groups_count: 0 })

    const wrapper = mountComponent()

    await wrapper.get('[data-testid="network-tag-edit-1"]').trigger('click')

    const modal = wrapper.get('[data-testid="network-tags-edit-modal"]')
    expect(modal.get('[data-testid="network-tags-edit-name"]').element.value).toBe('Scotland')

    await modal.get('[data-testid="network-tags-edit-name"]').setValue('Scotland (updated)')
    await wrapper.get('[data-testid="network-tags-edit-submit"]').trigger('click')
    await flushPromises()

    expect(store.updateTag).toHaveBeenCalledWith(1, 1, { name: 'Scotland (updated)', description: 'Old description' })
  })

  it('confirms deletion, warning about groups using the tag, then calls deleteTag', async () => {
    store.tags.data = [{ id: 1, name: 'Scotland', description: null, groups_count: 2 }]
    store.deleteTag = vi.fn().mockResolvedValue()

    const wrapper = mountComponent()

    await wrapper.get('[data-testid="network-tag-delete-1"]').trigger('click')

    const modal = wrapper.get('[data-testid="network-tags-delete-modal"]')
    expect(modal.text()).toContain('Are you sure you want to delete the tag "Scotland"?')
    expect(wrapper.get('[data-testid="network-tags-delete-warning"]').text()).toContain('2 group')

    await wrapper.get('[data-testid="network-tags-delete-confirm"]').trigger('click')
    await flushPromises()

    expect(store.deleteTag).toHaveBeenCalledWith(1, 1)
  })

  it('shows a load error with retry on failure', () => {
    store.tags.error = { status: 500 }
    store.fetchTags = vi.fn()

    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="network-tags-load-error"]').exists()).toBe(true)

    wrapper.get('[data-testid="network-tags-retry"]').trigger('click')
    expect(store.fetchTags).toHaveBeenCalledWith(1)
  })
})
