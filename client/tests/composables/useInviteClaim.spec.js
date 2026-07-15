import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { claimInvite } from '../../app/composables/useInviteClaim.js'
import { useAuthStore } from '../../app/stores/auth.js'
import { useToastStore } from '../../app/stores/toast.js'

describe('composables/useInviteClaim', () => {
  let navigateToMock
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    mockApi = {
      auth: {
        claimInvite: vi.fn(),
      },
    }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('redirects to /login carrying invite_code/invite_type/redirect when logged out', async () => {
    await claimInvite({
      code: 'abc123',
      inviteType: 'group',
      viewPathPrefix: '/group/view/',
      joinedMessage: 'Joined!',
      currentPath: '/group/invite/abc123',
    })

    expect(mockApi.auth.claimInvite).not.toHaveBeenCalled()
    expect(navigateToMock).toHaveBeenCalledWith(
      '/login?invite_code=abc123&invite_type=group&redirect=%2Fgroup%2Finvite%2Fabc123'
    )
  })

  it('claims the invite and redirects to the resource page with a success toast when logged in', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane' }

    mockApi.auth.claimInvite.mockResolvedValueOnce({
      data: { type: 'group', id: 42, already_member: false },
    })

    await claimInvite({
      code: 'abc123',
      inviteType: 'group',
      viewPathPrefix: '/group/view/',
      joinedMessage: 'Excellent! You have joined the group.',
      currentPath: '/group/invite/abc123',
    })

    expect(mockApi.auth.claimInvite).toHaveBeenCalledWith({
      invite_code: 'abc123',
      invite_type: 'group',
    })
    expect(navigateToMock).toHaveBeenCalledWith('/group/view/42')

    const toastStore = useToastStore()
    expect(toastStore.toasts).toHaveLength(1)
    expect(toastStore.toasts[0]).toMatchObject({
      message: 'Excellent! You have joined the group.',
      variant: 'success',
    })
  })

  it('claims an event invite and redirects to the event page', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane' }

    mockApi.auth.claimInvite.mockResolvedValueOnce({
      data: { type: 'event', id: 7, already_member: false },
    })

    await claimInvite({
      code: 'xyz789',
      inviteType: 'event',
      viewPathPrefix: '/party/view/',
      joinedMessage: 'Excellent! You have joined the event.',
      currentPath: '/party/invite/xyz789',
    })

    expect(mockApi.auth.claimInvite).toHaveBeenCalledWith({
      invite_code: 'xyz789',
      invite_type: 'event',
    })
    expect(navigateToMock).toHaveBeenCalledWith('/party/view/7')
  })

  it('shows an error toast and redirects to /dashboard when the claim fails', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane' }

    mockApi.auth.claimInvite.mockRejectedValueOnce({
      status: 404,
      data: { message: 'Invite not found' },
    })

    await claimInvite({
      code: 'missing',
      inviteType: 'group',
      viewPathPrefix: '/group/view/',
      joinedMessage: 'Joined!',
      currentPath: '/group/invite/missing',
    })

    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')

    const toastStore = useToastStore()
    expect(toastStore.toasts[0]).toMatchObject({
      message: 'Invite not found',
      variant: 'danger',
    })
  })
})
