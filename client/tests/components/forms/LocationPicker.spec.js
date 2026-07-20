import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it, vi } from 'vitest'
import LocationPicker from '../../../app/components/forms/LocationPicker.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BFormGroupStub = { template: '<div><slot /></div>' }

function mountPicker(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(LocationPicker, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BFormGroup: BFormGroupStub },
    },
  })
}

describe('components/forms/LocationPicker', () => {
  it('emits update:location as the user types, and clears stale lat/lng', async () => {
    vi.stubGlobal('useNuxtApp', () => ({ $api: undefined }))
    const wrapper = mountPicker({ location: '', lat: 1, lng: 2 })

    await wrapper.find('[data-testid="location-picker-input"]').setValue('123 Main St')

    expect(wrapper.emitted('update:location')[0]).toEqual(['123 Main St'])
    expect(wrapper.emitted('update:lat')[0]).toEqual([null])
    expect(wrapper.emitted('update:lng')[0]).toEqual([null])
  })

  it('shows the geocode-failed message when hasError is true', () => {
    vi.stubGlobal('useNuxtApp', () => ({ $api: undefined }))
    const wrapper = mountPicker({ location: 'Nowhereville', hasError: true })
    expect(wrapper.find('[data-testid="location-picker-error"]').exists()).toBe(true)
  })

  it('fetches and shows suggestions after typing 3+ characters, and selecting one emits location/lat/lng/postcode', async () => {
    const autocomplete = vi.fn().mockResolvedValue({
      predictions: [{ place_id: 'abc', description: '10 Downing St, London' }],
    })
    const placeDetails = vi.fn().mockResolvedValue({
      result: {
        formatted_address: '10 Downing St, London, UK',
        geometry: { location: { lat: 51.5, lng: -0.12 } },
        address_components: [{ types: ['postal_code'], long_name: 'SW1A 2AA' }],
      },
    })
    vi.stubGlobal('useNuxtApp', () => ({ $api: { maps: { autocomplete, placeDetails } } }))

    vi.useFakeTimers()
    const wrapper = mountPicker({ location: '' })
    await wrapper.find('[data-testid="location-picker-input"]').setValue('10 Downing')
    await vi.advanceTimersByTimeAsync(300)
    vi.useRealTimers()
    await wrapper.vm.$nextTick()
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(autocomplete).toHaveBeenCalledWith('10 Downing')
    expect(wrapper.find('[data-testid="location-picker-suggestions"]').exists()).toBe(true)

    // Vue Test Utils' .trigger('mousedown') doesn't reliably fire a
    // @mousedown.prevent handler under happy-dom - dispatch the native
    // event directly instead.
    wrapper.find('[data-testid="location-picker-suggestion"]').element.dispatchEvent(
      new MouseEvent('mousedown', { bubbles: true, cancelable: true }),
    )
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(placeDetails).toHaveBeenCalledWith('abc')
    const locationEmits = wrapper.emitted('update:location')
    expect(locationEmits[locationEmits.length - 1]).toEqual(['10 Downing St, London, UK'])
    expect(wrapper.emitted('update:lat').at(-1)).toEqual([51.5])
    expect(wrapper.emitted('update:lng').at(-1)).toEqual([-0.12])
    expect(wrapper.emitted('update:postcode').at(-1)).toEqual(['SW1A 2AA'])
  })

  it('degrades silently to plain text entry when the maps API is unavailable', async () => {
    vi.stubGlobal('useNuxtApp', () => ({ $api: undefined }))
    const wrapper = mountPicker({ location: '' })

    await wrapper.find('[data-testid="location-picker-input"]').setValue('Somewhere long enough')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="location-picker-suggestions"]').exists()).toBe(false)
    expect(wrapper.emitted('update:location').at(-1)).toEqual(['Somewhere long enough'])
  })

  it('exposes manual lat/lng fields only when show-lat-lng is set', () => {
    vi.stubGlobal('useNuxtApp', () => ({ $api: undefined }))
    const withoutLatLng = mountPicker({ location: '' })
    expect(withoutLatLng.find('[data-testid="location-picker-lat"]').exists()).toBe(false)

    const withLatLng = mountPicker({ location: '', showLatLng: true })
    expect(withLatLng.find('[data-testid="location-picker-lat"]').exists()).toBe(true)
  })

  // GroupForm.vue's postcode field on /group/create (canEditPostcode false
  // during creation, gap 12) rendered with a plain white background instead
  // of develop's greyed-out disabled look - traced to Bootstrap 5 dropping
  // the `:disabled`/`[readonly]` pairing BootstrapVue's Bootstrap 4 had
  // (fixed in app/assets/css/_forms.scss). This pins the functional half -
  // the HTML `readonly` attribute itself - since jsdom doesn't apply the
  // CSS fix for a computed-style assertion to check.
  it('marks the postcode input readonly exactly when canEditPostcode is false', () => {
    vi.stubGlobal('useNuxtApp', () => ({ $api: undefined }))
    const locked = mountPicker({ location: '', canEditPostcode: false })
    expect(locked.find('[data-testid="location-picker-postcode"]').attributes('readonly')).toBeDefined()

    const unlocked = mountPicker({ location: '', canEditPostcode: true })
    expect(unlocked.find('[data-testid="location-picker-postcode"]').attributes('readonly')).toBeUndefined()
  })
})
