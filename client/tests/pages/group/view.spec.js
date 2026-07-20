import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupViewPage from '../../../app/pages/group/view/[id].vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

const BASE_GROUP = {
  id: 5,
  name: 'Fixers United',
  image: null,
  website: 'https://example.com',
  description: '<p>We fix things.</p>',
  location: { location: 'Anytown' },
  tags: [],
  archived_at: null,
  permissions: {
    can_edit: false,
    can_demote: false,
    can_see_delete: false,
    can_perform_delete: false,
    can_perform_archive: false,
  },
}

function mountPage() {
  // groups.read_more/read_less (gap 9's About read-more toggle) aren't in
  // the checked-in i18n/locales fixtures yet (they're regenerated from
  // lang/en/groups.php by `translations:export-client`, which this task
  // deliberately doesn't run) - overlay them here instead.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, groups: { ...en.groups, read_more: 'Read more', read_less: 'Read less' } } },
  })

  return mount(GroupViewPage, {
    global: {
      plugins: [i18n],
      stubs: GROUP_VIEW_STUBS,
    },
  })
}

describe('pages/group/view/[id]', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/group/view/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    groupsStore = useGroupsStore()
    groupsStore.fetchCurrent = vi.fn().mockResolvedValue(BASE_GROUP)
    groupsStore.fetchStats = vi.fn().mockResolvedValue(null)
    groupsStore.fetchEvents = vi.fn().mockResolvedValue([])
    groupsStore.fetchVolunteers = vi.fn().mockResolvedValue([])
    groupsStore.deleteGroup = vi.fn().mockResolvedValue({ archived: true })

    useSessionStore().config = { discourse_url: 'https://talk.example.com' }
  })

  it('fetches the group, stats, events and volunteers for the routed id on mount', () => {
    mountPage()

    expect(groupsStore.fetchCurrent).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchStats).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchEvents).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchVolunteers).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton and no header while loading', () => {
    groupsStore.current.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-view-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-view-header"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls the fetches again', async () => {
    groupsStore.current.error = { status: 404 }

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-view-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-view-retry"]').trigger('click')
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  it('renders the header: image class hook, name, bold location and website', () => {
    groupsStore.current.data = BASE_GROUP

    const wrapper = mountPage()

    expect(wrapper.find('img.groupImage').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-view-name"]').text()).toBe('Fixers United')
    const location = wrapper.find('[data-testid="group-view-location"]')
    expect(location.text()).toBe('Anytown')
    expect(location.classes()).toContain('fw-bold')
    expect(wrapper.find('[data-testid="group-view-website"]').exists()).toBe(true)
  })

  it('renders a single GROUP ACTIONS dropdown instead of loose header buttons (gap 1)', () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    const dropdown = wrapper.find('[data-testid="group-actions-dropdown"]')
    expect(dropdown.exists()).toBe(true)
    expect(dropdown.text()).toContain('Group actions')
  })

  describe('description read-more/read-less (gap 9)', () => {
    it('renders a short description in full with no toggle', () => {
      groupsStore.current.data = BASE_GROUP
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="group-view-description-toggle"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-view-description-truncated"]').exists()).toBe(false)
      expect(wrapper.text()).toContain('We fix things.')
    })

    it('truncates a long description behind a read-more toggle, then expands it', async () => {
      const longText = 'A'.repeat(500)
      groupsStore.current.data = { ...BASE_GROUP, description: `<p>${longText}</p>` }
      const wrapper = mountPage()

      const truncated = wrapper.find('[data-testid="group-view-description-truncated"]')
      expect(truncated.exists()).toBe(true)
      expect(truncated.text().length).toBeLessThan(longText.length)
      expect(truncated.text().endsWith('...')).toBe(true)

      const toggle = wrapper.find('[data-testid="group-view-description-toggle"]')
      expect(toggle.text()).toBe('Read more')

      await toggle.trigger('click')
      expect(wrapper.find('[data-testid="group-view-description-truncated"]').exists()).toBe(false)
      expect(wrapper.text()).toContain(longText)
      expect(wrapper.find('[data-testid="group-view-description-toggle"]').text()).toBe('Read less')
    })
  })

  it('shows a mail icon before the mailto link when the group has an email', () => {
    groupsStore.current.data = { ...BASE_GROUP, email: 'group@example.com' }
    const wrapper = mountPage()

    const email = wrapper.find('[data-testid="group-view-email"]')
    expect(email.find('svg').exists()).toBe(true)
    expect(email.find('a').attributes('href')).toBe('mailto:group@example.com')
  })

  describe('About section (gap 4, 15)', () => {
    it('shows the archived badge in the About section, not the header, when archived', () => {
      groupsStore.current.data = { ...BASE_GROUP, archived_at: '2024-01-01T00:00:00Z' }
      const wrapper = mountPage()

      const description = wrapper.find('[data-testid="group-view-description"]')
      expect(description.find('[data-testid="group-view-archived"]').exists()).toBe(true)

      const header = wrapper.find('[data-testid="group-view-header"]')
      expect(header.find('[data-testid="group-view-archived"]').exists()).toBe(false)
    })

    it('shows tags in the header regardless of archived state', () => {
      groupsStore.current.data = { ...BASE_GROUP, tags: [{ id: 1, name: 'Repair' }] }
      const wrapper = mountPage()

      const header = wrapper.find('[data-testid="group-view-header"]')
      expect(header.find('[data-testid="group-view-tag-1"]').exists()).toBe(true)
    })

    it('links to the Discourse group conversation when discourse_group + config are present', () => {
      groupsStore.current.data = { ...BASE_GROUP, discourse_group: 'fixers-united' }
      const wrapper = mountPage()

      const talk = wrapper.find('[data-testid="group-view-talk"]')
      expect(talk.exists()).toBe(true)
      expect(talk.find('a').attributes('href')).toBe('https://talk.example.com/g/fixers-united')
    })

    it('does not render a Discourse link when the group has no discourse_group', () => {
      groupsStore.current.data = BASE_GROUP
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="group-view-talk"]').exists()).toBe(false)
    })
  })

  describe('permission-gated GROUP ACTIONS items (gap 1, 2)', () => {
    it('shows Edit only when can_edit is true', () => {
      groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-edit"]').exists()).toBe(true)

      groupsStore.current.data = BASE_GROUP
      const wrapperNoEdit = mountPage()
      expect(wrapperNoEdit.find('[data-testid="group-actions-edit"]').exists()).toBe(false)
    })

    it('shows Add event and Export group data only when can_edit is true', () => {
      groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="group-actions-add-event"]').attributes('href')).toBe('/party/create/5')
      expect(wrapper.find('[data-testid="group-actions-export"]').attributes('href')).toBe('/export/devices/group/5')
    })

    it('hides Archive entirely when can_perform_archive is false', () => {
      groupsStore.current.data = BASE_GROUP
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-archive"]').exists()).toBe(false)
    })

    it('shows Archive (not Delete) for a coordinator/admin when can_perform_archive is true', async () => {
      groupsStore.current.data = {
        ...BASE_GROUP,
        permissions: { ...BASE_GROUP.permissions, can_edit: true, can_perform_archive: true },
      }
      const wrapper = mountPage()

      const item = wrapper.find('[data-testid="group-actions-archive"]')
      expect(item.exists()).toBe(true)
      // labelled Archive, not Delete - it hits the reversible archive endpoint
      expect(item.text()).toBe('Archive group')
      expect(wrapper.find('[data-testid="group-view-delete"]').exists()).toBe(false)

      await item.trigger('click')
      expect(wrapper.find('[data-testid="group-view-archive-confirm"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-view-archive-confirm"]').trigger('click')
      expect(groupsStore.deleteGroup).toHaveBeenCalledWith(5)
    })

    it('shows Invite when can_edit is true even if not a member', () => {
      groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
      groupsStore.memberIds = []
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-invite"]').exists()).toBe(true)
    })

    it('shows Invite when a member even without can_edit', () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = [5]
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-invite"]').exists()).toBe(true)
    })

    it('hides Invite when neither can_edit nor a member', () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = []
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-invite"]').exists()).toBe(false)
    })
  })

  describe('join/leave integration (via GROUP ACTIONS)', () => {
    it('shows a Join item wired to the store when not a member', async () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = []
      groupsStore.join = vi.fn().mockResolvedValue()

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-join"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-actions-join"]').trigger('click')
      expect(groupsStore.join).toHaveBeenCalledWith(5)
    })

    it('shows a Leave item wired to the store when a member', async () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = [5]
      groupsStore.leave = vi.fn().mockResolvedValue()

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-actions-leave"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-actions-leave"]').trigger('click')
      expect(groupsStore.leave).toHaveBeenCalledWith(5)
    })
  })

  it('opens the invite modal from the GROUP ACTIONS invite item', async () => {
    groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-invite-form"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-actions-invite"]').trigger('click')
    expect(wrapper.find('[data-testid="group-invite-form"]').exists()).toBe(true)
  })

  it('opens the share-stats modal from the GROUP ACTIONS share-stats item (gap 2)', async () => {
    groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-share-stats-modal"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-actions-share-stats"]').trigger('click')
    expect(wrapper.find('[data-testid="group-share-stats-modal"]').exists()).toBe(true)
  })

  // develop opens two DIFFERENT modals from what look like the same "share
  // stats" idea: GROUP ACTIONS' "Share group stats" dropdown item (above)
  // opens the embed-code modal (GroupShareStatsModal), but the CO2 card's
  // own "Share this" link (GroupStats.vue's @share-stats, StatsImpact.vue's
  // `share` handler in develop) opens the canvas-painted social-image
  // generator (StatsShareModal/StatsShare.vue) instead - a separate
  // feature. Confirms the CO2 card's button is wired to the canvas modal,
  // not the embed one, and that they don't both open together.
  it('opens the canvas share-image modal (not the embed modal) from the CO2 card\'s Share this link', async () => {
    groupsStore.current.data = BASE_GROUP
    groupsStore.stats.data = {
      group_stats: { co2_total: 600, waste_total: 12, dead_devices: 0, repairable_devices: 0, no_weight: 0 },
    }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="stats-share-image-modal"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-share-stats-modal"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-stats-share"]').trigger('click')

    expect(wrapper.find('[data-testid="stats-share-image-modal"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-share-stats-modal"]').exists()).toBe(false)
  })
})
