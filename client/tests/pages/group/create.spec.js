import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupCreatePage from '../../../app/pages/group/create.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// GroupForm.vue itself is unit-tested in
// tests/components/groups/GroupForm.spec.js - stub it here to a single
// button that emits 'created', so this spec is only about the page's own
// job: heading/copy and the post-create redirect.
const GroupFormStub = {
  emits: ['created'],
  template: '<button data-testid="stub-create" @click="$emit(\'created\', 77)" />',
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupCreatePage, {
    global: {
      plugins: [i18n],
      stubs: { GroupForm: GroupFormStub },
    },
  })
}

describe('pages/group/create', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('renders the new-group heading and page test hook', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-create-page"]').exists()).toBe(true)
    expect(wrapper.find('h1').text()).toBe('Create a new group')
  })

  it('navigates to /group/edit/{id} when GroupForm emits created (Playwright expects **/edit/**)', async () => {
    const wrapper = mountPage()
    await wrapper.find('[data-testid="stub-create"]').trigger('click')

    expect(navigateToMock).toHaveBeenCalledWith('/group/edit/77')
  })
})
