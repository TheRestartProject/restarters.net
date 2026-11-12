import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupOptions from '../../../app/components/groups/GroupOptions.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function feature(overrides = {}) {
  return { enabled: true, source: 'default', locked: false, group_setting: null, inherited: true, set_by: [], ...overrides }
}

function group(overrides = {}) {
  return {
    id: 3,
    features: {
      item_weight: feature(),
      custom_fields: feature({ enabled: false, inherited: false }),
    },
    custom_fields: [],
    ...overrides,
  }
}

function mountOptions(g = group()) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  return mount(GroupOptions, {
    props: { group: g },
    global: { plugins: [i18n], stubs: { BAlert: { template: '<div v-bind="$attrs"><slot /></div>' }, BButton: { template: '<button v-bind="$attrs"><slot /></button>' } } },
  })
}

const sel = (w, key) => w.find(`[data-testid="group-option-${key}"]`)

describe('components/groups/GroupOptions', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useGroupsStore()
    store.updateOptions = vi.fn().mockResolvedValue({})
  })

  it('titles the card Options and offers each feature or not, worded by what it does, with the default marked', () => {
    const wrapper = mountOptions()

    expect(wrapper.find('[data-testid="group-options-title"]').text()).toBe('Options')
    expect(sel(wrapper, 'item_weight').findAll('option').map((o) => o.text())).toEqual([
      'Collect item weights (the default)',
      "Don't collect item weights",
    ])
    expect(sel(wrapper, 'custom_fields').findAll('option').map((o) => o.text())).toEqual([
      'Add fields of our own to the item form',
      'Use the standard item form only (the default)',
    ])
    expect(sel(wrapper, 'item_weight').element.value).toBe('')
  })

  it('shows the group\'s own setting, and one that is the same as the default as the default', () => {
    const wrapper = mountOptions(group({ features: { item_weight: feature({ group_setting: false, enabled: false }), custom_fields: feature({ group_setting: true, enabled: true, inherited: false }) } }))

    expect(sel(wrapper, 'item_weight').element.value).toBe('off')
    expect(sel(wrapper, 'custom_fields').element.value).toBe('on')

    const same = mountOptions(group({ features: { item_weight: feature({ group_setting: true }), custom_fields: feature({ enabled: false, inherited: false }) } }))
    expect(sel(same, 'item_weight').element.value).toBe('')
  })

  it('disables a locked feature and says which network set it', () => {
    const wrapper = mountOptions(group({
      features: {
        item_weight: feature({ locked: true, source: 'network', enabled: false, set_by: ['Repair Together', 'Other Net'] }),
        custom_fields: feature({ enabled: false, inherited: false }),
      },
    }))

    expect(sel(wrapper, 'item_weight').attributes('disabled')).toBeDefined()
    expect(sel(wrapper, 'item_weight').findAll('option').map((o) => o.text())).toEqual(["Don't collect item weights"])
    expect(wrapper.find('[data-testid="group-option-item_weight-locked"]').text()).toBe('Set by Repair Together, Other Net')
    expect(sel(wrapper, 'custom_fields').attributes('disabled')).toBeUndefined()
  })

  it('saves the features, leaving locked ones out', async () => {
    const wrapper = mountOptions(group({
      features: {
        item_weight: feature({ locked: true, enabled: true, set_by: ['N'] }),
        custom_fields: feature({ enabled: false, inherited: false }),
      },
    }))

    await sel(wrapper, 'custom_fields').setValue('on')
    await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
    await flushPromises()

    // Turning custom fields on brings the (so far empty) list of fields with it.
    expect(store.updateOptions).toHaveBeenCalledWith(3, { features: { custom_fields: true }, custom_fields: [] })
    expect(wrapper.find('[data-testid="group-options-saved"]').exists()).toBe(true)
  })

  it('saves the default choice as following the default', async () => {
    // So a network that changes its default changes the group too.
    const wrapper = mountOptions(group({ features: { item_weight: feature({ group_setting: false, enabled: false }), custom_fields: feature({ enabled: false, inherited: false }) } }))

    await sel(wrapper, 'item_weight').setValue('')
    await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
    await flushPromises()

    expect(store.updateOptions).toHaveBeenCalledWith(3, { features: { item_weight: null, custom_fields: null } })
  })

  it('shows the group as it is now when the group changes, as it does after saving', async () => {
    const wrapper = mountOptions(group({ features: { item_weight: feature({ group_setting: true }), custom_fields: feature({ group_setting: true, enabled: true }) } }))

    await wrapper.setProps({
      group: group({
        features: {
          item_weight: feature({ enabled: false, source: 'network', locked: true, set_by: ['Strict'] }),
          custom_fields: feature({ group_setting: true, enabled: true }),
        },
        custom_fields: [{ key: 'town', label: 'Town', type: 'text', options: [] }],
      }),
    })
    await wrapper.vm.$nextTick()

    expect(sel(wrapper, 'item_weight').element.value).toBe('locked')
    expect(sel(wrapper, 'item_weight').attributes('disabled')).toBeDefined()
    expect(wrapper.find('[data-testid="group-custom-field-row-0"] input').element.value).toBe('Town')
  })

  it('keeps unsaved edits when the group is refreshed', async () => {
    const g = group({
      features: { item_weight: feature(), custom_fields: feature({ group_setting: true, enabled: true }) },
      custom_fields: [{ key: 'town', label: 'Town', type: 'text', options: [] }],
    })
    const wrapper = mountOptions(g)
    await wrapper.find('[data-testid="group-custom-field-label-0"]').setValue('Home town')
    await sel(wrapper, 'item_weight').setValue('off')

    // The same group, fetched again (for example after the main group form is saved).
    await wrapper.setProps({ group: JSON.parse(JSON.stringify(g)) })
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="group-custom-field-label-0"]').element.value).toBe('Home town')
    expect(sel(wrapper, 'item_weight').element.value).toBe('off')
  })

  it('shows the group as saved after saving, even when what applies has changed', async () => {
    const wrapper = mountOptions(group({ features: { item_weight: feature(), custom_fields: feature({ enabled: false, inherited: false }) } }))
    store.updateOptions = vi.fn().mockImplementation(async () => {
      // The store merges the response into the group: a network has since set item weights to always off.
      await wrapper.setProps({
        group: group({ features: { item_weight: feature({ enabled: false, source: 'network', locked: true, set_by: ['Strict'] }), custom_fields: feature({ enabled: false, inherited: false }) } }),
      })
    })
    await sel(wrapper, 'item_weight').setValue('off')

    await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
    await flushPromises()
    await wrapper.vm.$nextTick()

    expect(sel(wrapper, 'item_weight').element.value).toBe('locked')
    expect(sel(wrapper, 'item_weight').attributes('disabled')).toBeDefined()
  })

  it('shows what was saved, from the response, even if the group passed in is not refreshed', async () => {
    const wrapper = mountOptions(group({ features: { item_weight: feature(), custom_fields: feature({ group_setting: true, enabled: true }) } }))
    store.updateOptions = vi.fn().mockResolvedValue({
      features: { item_weight: feature({ group_setting: false, enabled: false, source: 'group' }), custom_fields: feature({ group_setting: true, enabled: true }) },
      custom_fields: [{ key: 'town', label: 'Town', type: 'text', options: [] }],
    })
    await sel(wrapper, 'item_weight').setValue('off')

    await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
    await flushPromises()

    expect(sel(wrapper, 'item_weight').element.value).toBe('off')
    expect(wrapper.find('[data-testid="group-custom-field-label-0"]').element.value).toBe('Town')
  })

  it('shows an error when saving fails', async () => {
    store.updateOptions = vi.fn().mockRejectedValue(new Error('422'))
    const wrapper = mountOptions()

    await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="group-options-error"]').exists()).toBe(true)
  })

  describe('custom item fields editor', () => {
    const withFields = () => group({
      features: { item_weight: feature(), custom_fields: feature({ enabled: true, group_setting: true, inherited: false }) },
      custom_fields: [
        { key: 'town', label: 'Town', type: 'text', options: [] },
        { key: 'visit', label: 'First visit?', type: 'choice', options: ['Yes', 'No'] },
      ],
    })

    it('is hidden when custom fields are off', () => {
      expect(mountOptions().find('[data-testid="group-custom-fields"]').exists()).toBe(false)
    })

    it('lists the fields and explains removal', () => {
      const wrapper = mountOptions(withFields())

      expect(wrapper.findAll('[data-testid^="group-custom-field-row-"]')).toHaveLength(2)
      expect(wrapper.find('[data-testid="group-custom-field-label-1"]').element.value).toBe('First visit?')
      expect(wrapper.find('[data-testid="group-custom-field-type-1"]').element.value).toBe('choice')
      expect(wrapper.find('[data-testid="group-custom-field-options-1"]').element.value).toBe('Yes\nNo')
      expect(wrapper.find('[data-testid="group-custom-field-options-0"]').exists()).toBe(false)
      expect(wrapper.text()).toContain('Removing a field hides its values; it does not delete them.')
    })

    it('appears as soon as the select is turned on', async () => {
      const wrapper = mountOptions()
      await sel(wrapper, 'custom_fields').setValue('on')
      expect(wrapper.find('[data-testid="group-custom-fields"]').exists()).toBe(true)
    })

    it('sends the full list with existing keys, a renamed label, a new field and one removed', async () => {
      const wrapper = mountOptions(withFields())

      await wrapper.find('[data-testid="group-custom-field-label-0"]').setValue('Home town')
      await wrapper.find('[data-testid="group-custom-field-remove-1"]').trigger('click')
      await wrapper.find('[data-testid="group-custom-field-add"]').trigger('click')
      await wrapper.find('[data-testid="group-custom-field-label-1"]').setValue('Colour')
      await wrapper.find('[data-testid="group-custom-field-type-1"]').setValue('choice')
      await wrapper.find('[data-testid="group-custom-field-options-1"]').setValue('Red\n\n Blue \n')
      await wrapper.find('[data-testid="group-options-form"]').trigger('submit')
      await flushPromises()

      expect(store.updateOptions).toHaveBeenCalledWith(3, {
        features: { item_weight: null, custom_fields: true },
        custom_fields: [
          { key: 'town', label: 'Home town', type: 'text' },
          { label: 'Colour', type: 'choice', options: ['Red', 'Blue'] },
        ],
      })
    })

    it('stops adding at 10 fields', async () => {
      const wrapper = mountOptions(withFields())
      for (let i = 0; i < 8; i++) await wrapper.find('[data-testid="group-custom-field-add"]').trigger('click')

      expect(wrapper.findAll('[data-testid^="group-custom-field-row-"]')).toHaveLength(10)
      expect(wrapper.find('[data-testid="group-custom-field-add"]').attributes('disabled')).toBeDefined()
    })
  })
})
