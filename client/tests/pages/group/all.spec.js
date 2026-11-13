import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupAllPage from '../../../app/pages/group/all.vue'

// PR 887 (RES-1995) reworks /group to two tabs, dropping the separate All
// Groups list - every group is on the map, with the list panel below it
// narrowed by the viewport. This route only forwards to /group/map so old
// links and bookmarks keep working.
describe('pages/group/all', () => {
  let navigateToMock

  beforeEach(() => {
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  // The networks page links here as /group/all?network=N - the filter must
  // survive the forward.
  it('redirects to /group/map, preserving the query string', () => {
    vi.stubGlobal('useRoute', () => ({ query: { network: '5' }, params: {}, fullPath: '/group/all?network=5' }))

    mount(GroupAllPage)

    expect(navigateToMock).toHaveBeenCalledWith({ path: '/group/map', query: { network: '5' } }, { replace: true })
  })
})
