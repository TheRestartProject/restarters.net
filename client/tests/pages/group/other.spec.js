import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupOtherPage from '../../../app/pages/group/other.vue'

// Live's /group/other is the "Find a group" tab, which groups.no_groups_mine links to; in the client that is
// /group/map.
describe('pages/group/other', () => {
  let navigateToMock

  beforeEach(() => {
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('redirects to /group/map, preserving the query string', () => {
    mount(GroupOtherPage)

    expect(navigateToMock).toHaveBeenCalledWith({ path: '/group/map', query: {} }, { replace: true })
  })
})
