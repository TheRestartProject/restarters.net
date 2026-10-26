import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import SkillsTab from '../../../app/components/profile/SkillsTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(SkillsTab, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

const SKILLS_RESPONSE = {
  categories: [{ id: 1, label: 'general.repair_skills', skills: [{ id: 10, name: 'general.repair_skills' }] }],
  selected: [10],
}

describe('components/profile/SkillsTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('fetches GET /users/me/skills on mount and renders the category/skill options', async () => {
    profileStore.fetchSkills = vi.fn().mockResolvedValue(SKILLS_RESPONSE)
    profileStore.skills.data = SKILLS_RESPONSE

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.fetchSkills).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="skills-select"]').findAll('option')).toHaveLength(1)
  })

  it('saves the selected skill ids as {tags: [...]} on submit', async () => {
    profileStore.fetchSkills = vi.fn().mockResolvedValue(SKILLS_RESPONSE)
    profileStore.skills.data = SKILLS_RESPONSE
    profileStore.updateSkills = vi.fn().mockResolvedValue({ tags: [10] })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.find('[data-testid="skills-select"]').setValue(['10'])
    await wrapper.find('[data-testid="skills-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updateSkills).toHaveBeenCalledWith({ tags: [10] })
    expect(wrapper.text()).toContain('Skills updated!')
  })

  it('shows an error state when the load fails', async () => {
    profileStore.fetchSkills = vi.fn().mockRejectedValue({ status: 500 })
    profileStore.skills.error = { status: 500 }

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="skills-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="skills-form"]').exists()).toBe(false)
  })

  describe('editing someone else\'s profile (isOwnProfile: false)', () => {
    it('fetches via fetchUserSkills(targetId) and saves via updateUserSkills(targetId, payload), not the me/* actions', async () => {
      profileStore.fetchUserSkills = vi.fn().mockResolvedValue(SKILLS_RESPONSE)
      profileStore.userSkills.data = SKILLS_RESPONSE
      profileStore.updateUserSkills = vi.fn().mockResolvedValue({ tags: [10] })
      profileStore.fetchSkills = vi.fn()
      profileStore.updateSkills = vi.fn()

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.fetchUserSkills).toHaveBeenCalledWith(42)
      expect(profileStore.fetchSkills).not.toHaveBeenCalled()

      await wrapper.find('[data-testid="skills-select"]').setValue(['10'])
      await wrapper.find('[data-testid="skills-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.updateUserSkills).toHaveBeenCalledWith(42, { tags: [10] })
      expect(profileStore.updateSkills).not.toHaveBeenCalled()
    })
  })
})
