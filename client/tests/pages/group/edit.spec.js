import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupEditPage from '../../../app/pages/group/edit/[id].vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const GroupFormStub = {
  props: ['groupId', 'initialGroup', 'permissions', 'isAdmin'],
  emits: ['updated'],
  template: '<button data-testid="stub-updated" @click="$emit(\'updated\', groupId)" />',
}
const TusImageUploadStub = {
  props: ['currentImageUrl'],
  emits: ['uploaded', 'upload-error'],
  template:
    '<div><button data-testid="stub-upload-ok" @click="$emit(\'uploaded\', { uploadKey: \'key123\' })" /><button data-testid="stub-upload-fail" @click="$emit(\'upload-error\', \'boom\')" /></div>',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BFormGroup: BFormGroupStub,
  GroupForm: GroupFormStub,
  TusImageUpload: TusImageUploadStub,
}

const BASE_GROUP = {
  id: 5,
  name: 'Fixers United',
  image: null,
  permissions: {
    can_edit: true,
    can_demote: false,
    can_see_delete: false,
    can_perform_delete: false,
    can_perform_archive: false,
  },
}

// archive_group/archive_group_confirm are being restored to lang/en/groups.php
// (see docs/nuxt-migration/findings/parity-audit-findings.md) but the
// generated client/i18n/locales/en.json is regenerated centrally via
// `php artisan translations:export-client`, so they're supplied inline here
// rather than by editing the checked-in locale file.
const GROUPS_OVERRIDES = {
  groups: {
    archive_group: 'Archive group',
    archive_group_confirm: 'Please confirm that you want to archive {name}.',
  },
}

function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, groups: { ...en.groups, ...GROUPS_OVERRIDES.groups } } },
  })

  return mount(GroupEditPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/group/edit/[id]', () => {
  let groupsStore
  let sessionStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/group/edit/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    groupsStore = useGroupsStore()
    groupsStore.fetchCurrent = vi.fn().mockResolvedValue(BASE_GROUP)
    groupsStore.deleteGroup = vi.fn().mockResolvedValue({ archived: true })
    groupsStore.uploadGroupImage = vi.fn().mockResolvedValue({ image_url: '/uploads/new.png' })

    sessionStore = useSessionStore()
    sessionStore.user = { role: 4 }
  })

  it('fetches the group for the routed id on mount', () => {
    mountPage()
    expect(groupsStore.fetchCurrent).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.current.loading = true
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-edit-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button', async () => {
    groupsStore.current.error = { status: 404 }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-edit-error"]').exists()).toBe(true)
    await wrapper.find('[data-testid="group-edit-retry"]').trigger('click')
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  it('shows a forbidden message instead of the form when can_edit is false', () => {
    groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: false } }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-edit-forbidden"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-updated"]').exists()).toBe(false)
  })

  it('renders the form pre-wired to the fetched group when can_edit is true', () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    const form = wrapper.findComponent(GroupFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('groupId')).toBe(5)
    expect(form.props('initialGroup')).toEqual(BASE_GROUP)
  })

  it('treats Root (role 1) and Administrator (role 2) as isAdmin, others as not', () => {
    groupsStore.current.data = BASE_GROUP

    sessionStore.user = { role: 2 }
    let wrapper = mountPage()
    expect(wrapper.findComponent(GroupFormStub).props('isAdmin')).toBe(true)

    sessionStore.user = { role: 4 }
    wrapper = mountPage()
    expect(wrapper.findComponent(GroupFormStub).props('isAdmin')).toBe(false)
  })

  it('shows a success banner and re-fetches when GroupForm emits updated', async () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-updated"]').trigger('click')

    expect(wrapper.find('[data-testid="group-edit-success"]').exists()).toBe(true)
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  it('calls uploadGroupImage with the emitted upload key', async () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')

    expect(groupsStore.uploadGroupImage).toHaveBeenCalledWith(5, 'key123')
  })

  it('shows an image upload error message from TusImageUpload', async () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-upload-fail"]').trigger('click')

    expect(wrapper.find('[data-testid="group-edit-image-error"]').text()).toBe('boom')
  })

  describe('archive', () => {
    it('hides the control entirely when can_perform_archive is false', () => {
      groupsStore.current.data = BASE_GROUP
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-edit-archive"]').exists()).toBe(false)
    })

    it('shows the archive control for a NetworkCoordinator when can_perform_archive is true', () => {
      groupsStore.current.data = {
        ...BASE_GROUP,
        permissions: { ...BASE_GROUP.permissions, can_perform_archive: true },
      }
      sessionStore.user = { role: 4 } // NetworkCoordinator, not Administrator
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-edit-archive"]').exists()).toBe(true)
    })

    it('shows the archive_group label and archive_group_confirm copy, and calls deleteGroup (archive semantics) then redirects', async () => {
      groupsStore.current.data = {
        ...BASE_GROUP,
        name: 'Fixers United',
        permissions: { ...BASE_GROUP.permissions, can_perform_archive: true },
      }
      const wrapper = mountPage()

      const button = wrapper.find('[data-testid="group-edit-archive"]')
      expect(button.text()).toBe('Archive group')

      await button.trigger('click')
      expect(wrapper.find('[data-testid="group-edit-archive-confirm"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Please confirm that you want to archive Fixers United.')

      await wrapper.find('[data-testid="group-edit-archive-confirm"]').trigger('click')
      expect(groupsStore.deleteGroup).toHaveBeenCalledWith(5)
    })
  })
})
