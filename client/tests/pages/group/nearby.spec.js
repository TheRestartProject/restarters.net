import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupNearbyPage from '../../../app/pages/group/nearby.vue'

// PR 887 (RES-1995) reworks /group to two tabs, folding "Find a group"
// into the map tab. The port's plain nearby list is gone; this route only
// forwards to /group/map so old links and bookmarks (and map.vue's
// group_count_none inline link) keep working.
describe('pages/group/nearby', () => {
  let navigateToMock

  beforeEach(() => {
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('redirects to /group/map, preserving the query string', () => {
    mount(GroupNearbyPage)

    expect(navigateToMock).toHaveBeenCalledWith({ path: '/group/map', query: {} }, { replace: true })
  })
})
