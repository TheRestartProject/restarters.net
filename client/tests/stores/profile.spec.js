import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useProfileStore } from '../../app/stores/profile.js'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import { useToastStore } from '../../app/stores/toast.js'

describe('stores/profile', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      user: {
        get: vi.fn(),
        getEmailPreferences: vi.fn(),
        updateEmailPreferences: vi.fn(),
        getCalendars: vi.fn(),
        getLanguage: vi.fn(),
        updateLanguage: vi.fn(),
        getProfileInfo: vi.fn(),
        updateProfileInfo: vi.fn(),
        getSkills: vi.fn(),
        updateSkills: vi.fn(),
        updatePassword: vi.fn(),
        updatePhoto: vi.fn(),
        deleteAccount: vi.fn(),
        getRepairDirectoryOptions: vi.fn(),
        updateRepairDirectoryRole: vi.fn(),
        getAdminSettings: vi.fn(),
        updateAdminSettings: vi.fn(),
        getUserProfile: vi.fn(),
        updateUserProfile: vi.fn(),
        getUserSkills: vi.fn(),
        updateUserSkills: vi.fn(),
        getUserEmailPreferences: vi.fn(),
        updateUserEmailPreferences: vi.fn(),
        updateUserPassword: vi.fn(),
        deleteUser: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchEmailPreferences', () => {
    it('sets loading while in flight and populates data on success', async () => {
      let resolveFetch
      mockApi.user.getEmailPreferences.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        }),
      )

      const store = useProfileStore()
      const promise = store.fetchEmailPreferences()

      expect(store.emailPreferences.loading).toBe(true)

      resolveFetch({ data: { invites: true } })
      await promise

      expect(store.emailPreferences.loading).toBe(false)
      expect(store.emailPreferences.error).toBeNull()
      expect(store.emailPreferences.data).toEqual({ invites: true })
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.user.getEmailPreferences.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      await expect(store.fetchEmailPreferences()).rejects.toEqual(apiError)
      expect(store.emailPreferences.error).toEqual(apiError)
    })
  })

  describe('updateEmailPreferences', () => {
    it('calls PATCH with the exact payload shape and stores the response', async () => {
      mockApi.user.updateEmailPreferences.mockResolvedValueOnce({ data: { invites: false } })

      const store = useProfileStore()
      const data = await store.updateEmailPreferences({ invites: false })

      expect(mockApi.user.updateEmailPreferences).toHaveBeenCalledWith({ invites: false })
      expect(data).toEqual({ invites: false })
      expect(store.emailPreferences.data).toEqual({ invites: false })
    })

    it('does not catch - lets the caller render field errors (422 convention)', async () => {
      const apiError = { status: 422, data: { errors: { invites: ['bad'] } } }
      mockApi.user.updateEmailPreferences.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      await expect(store.updateEmailPreferences({ invites: true })).rejects.toEqual(apiError)
    })
  })

  describe('fetchCalendars / fetchLanguage', () => {
    it('fetchCalendars populates data', async () => {
      mockApi.user.getCalendars.mockResolvedValueOnce({ data: { user_url: 'x', groups: [], is_admin: false, admin_all_events_url: null, group_areas: [] } })

      const store = useProfileStore()
      await store.fetchCalendars()

      expect(store.calendars.data.user_url).toBe('x')
    })

    it('fetchLanguage populates data', async () => {
      mockApi.user.getLanguage.mockResolvedValueOnce({ data: { language: 'en', supported: [{ code: 'en', native: 'English' }] } })

      const store = useProfileStore()
      await store.fetchLanguage()

      expect(store.language.data.language).toBe('en')
    })
  })

  describe('updateLanguage', () => {
    it('updates local state and the session store user language', async () => {
      mockApi.user.updateLanguage.mockResolvedValueOnce({ data: { language: 'fr' } })

      const store = useProfileStore()
      store.language.data = { language: 'en', supported: [] }

      const sessionStore = useSessionStore()
      sessionStore.user = { id: 1, language: 'en' }

      await store.updateLanguage({ language: 'fr' })

      expect(mockApi.user.updateLanguage).toHaveBeenCalledWith({ language: 'fr' })
      expect(store.language.data.language).toBe('fr')
      expect(sessionStore.user.language).toBe('fr')
    })
  })

  describe('fetchProfileInfo / updateProfileInfo', () => {
    it('fetchProfileInfo populates data', async () => {
      mockApi.user.getProfileInfo.mockResolvedValueOnce({
        data: { name: 'Jane', email: 'jane@example.com', country_code: 'GB', location: '', age: '1990', gender: '', biography: '', countries: [], ages: [] },
      })

      const store = useProfileStore()
      await store.fetchProfileInfo()

      expect(store.info.data.name).toBe('Jane')
    })

    it('updateProfileInfo sends the exact legacy field names and merges the response', async () => {
      const store = useProfileStore()
      store.info.data = { name: 'Old', email: 'old@example.com', countries: [{ code: 'GB', name: 'UK' }], ages: ['1990'] }

      mockApi.user.updateProfileInfo.mockResolvedValueOnce({
        data: { name: 'New', email: 'new@example.com', country_code: 'GB', location: 'London', age: '1990', gender: '', biography: '' },
      })

      const sessionStore = useSessionStore()
      sessionStore.user = { id: 1, name: 'Old', email: 'old@example.com' }

      await store.updateProfileInfo({
        name: 'New',
        email: 'new@example.com',
        country: 'GB',
        townCity: 'London',
        age: '1990',
        gender: '',
        biography: '',
      })

      expect(mockApi.user.updateProfileInfo).toHaveBeenCalledWith({
        name: 'New',
        email: 'new@example.com',
        country: 'GB',
        townCity: 'London',
        age: '1990',
        gender: '',
        biography: '',
      })
      // Option lists from the initial GET survive the merge.
      expect(store.info.data.countries).toEqual([{ code: 'GB', name: 'UK' }])
      expect(store.info.data.name).toBe('New')
      expect(sessionStore.user.name).toBe('New')
      expect(sessionStore.user.email).toBe('new@example.com')
    })
  })

  describe('fetchSkills / updateSkills', () => {
    it('updateSkills sends {tags} and stores the returned selection', async () => {
      const store = useProfileStore()
      store.skills.data = { categories: [], selected: [1] }

      mockApi.user.updateSkills.mockResolvedValueOnce({ data: { tags: [1, 2] } })

      const data = await store.updateSkills({ tags: [1, 2] })

      expect(mockApi.user.updateSkills).toHaveBeenCalledWith({ tags: [1, 2] })
      expect(data.tags).toEqual([1, 2])
      expect(store.skills.data.selected).toEqual([1, 2])
    })
  })

  describe('updatePassword', () => {
    it('sends the three legacy field names and does not catch errors', async () => {
      mockApi.user.updatePassword.mockResolvedValueOnce({ data: { success: true } })

      const store = useProfileStore()
      await store.updatePassword({
        current_password: 'old',
        new_password: 'newpass',
        new_password_confirmation: 'newpass',
      })

      expect(mockApi.user.updatePassword).toHaveBeenCalledWith({
        current_password: 'old',
        new_password: 'newpass',
        new_password_confirmation: 'newpass',
      })
    })

    it('rethrows a 422 for the caller to render per-field', async () => {
      const apiError = { status: 422, data: { errors: { current_password: ['Current Password does not match!'] } } }
      mockApi.user.updatePassword.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      await expect(
        store.updatePassword({ current_password: 'wrong', new_password: 'a', new_password_confirmation: 'a' }),
      ).rejects.toEqual(apiError)
    })
  })

  describe('uploadPhoto', () => {
    it('calls the API with the upload key and updates the session avatar_url', async () => {
      mockApi.user.updatePhoto.mockResolvedValueOnce({ data: { path: 'abc123.jpg' } })

      const store = useProfileStore()
      const sessionStore = useSessionStore()
      sessionStore.user = { id: 1, avatar_url: null }

      vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://api.test' } }))

      const data = await store.uploadPhoto('key123')

      expect(mockApi.user.updatePhoto).toHaveBeenCalledWith('key123')
      expect(data.path).toBe('abc123.jpg')
      expect(sessionStore.user.avatar_url).toBe('http://api.test/uploads/thumbnail_abc123.jpg')
    })
  })

  describe('deleteAccount', () => {
    it('clears local auth state on success', async () => {
      mockApi.user.deleteAccount.mockResolvedValueOnce({ data: { success: true } })

      const store = useProfileStore()
      const authStore = useAuthStore()
      authStore.token = 'sometoken'
      authStore.user = { id: 1 }

      await store.deleteAccount()

      expect(authStore.token).toBeNull()
      expect(authStore.user).toBeNull()
    })

    it('toasts and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.user.deleteAccount.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      const toastStore = useToastStore()

      await expect(store.deleteAccount()).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(1)
      expect(toastStore.toasts[0].variant).toBe('danger')
    })
  })

  describe('fetchRepairDirectoryOptions', () => {
    it('fetches and caches per target id, skipping a second call for the same id', async () => {
      mockApi.user.getRepairDirectoryOptions.mockResolvedValueOnce({
        data: { current: 0, options: [{ value: 0, key: 'profile.repair_dir_none', selected: true, disabled: false }] },
      })

      const store = useProfileStore()
      const first = await store.fetchRepairDirectoryOptions(5)
      const second = await store.fetchRepairDirectoryOptions(5)

      expect(mockApi.user.getRepairDirectoryOptions).toHaveBeenCalledTimes(1)
      expect(first).toEqual(second)
    })

    it('re-fetches for a different target id', async () => {
      mockApi.user.getRepairDirectoryOptions.mockResolvedValueOnce({ data: { current: 0, options: [] } })
      mockApi.user.getRepairDirectoryOptions.mockResolvedValueOnce({ data: { current: 1, options: [] } })

      const store = useProfileStore()
      await store.fetchRepairDirectoryOptions(5)
      await store.fetchRepairDirectoryOptions(6)

      expect(mockApi.user.getRepairDirectoryOptions).toHaveBeenCalledTimes(2)
      expect(mockApi.user.getRepairDirectoryOptions).toHaveBeenNthCalledWith(1, 5)
      expect(mockApi.user.getRepairDirectoryOptions).toHaveBeenNthCalledWith(2, 6)
    })
  })

  describe('repairDirectoryVisible getter', () => {
    it('is false when every option is disabled (not a Regional/Super Admin)', () => {
      const store = useProfileStore()
      store.repairDirectory.data = {
        current: 0,
        options: [
          { value: 0, disabled: true },
          { value: 3, disabled: true },
        ],
      }

      expect(store.repairDirectoryVisible).toBe(false)
    })

    it('is true when at least one option is enabled', () => {
      const store = useProfileStore()
      store.repairDirectory.data = {
        current: 0,
        options: [
          { value: 0, disabled: false },
          { value: 3, disabled: true },
        ],
      }

      expect(store.repairDirectoryVisible).toBe(true)
    })

    it('is false while nothing has loaded yet', () => {
      const store = useProfileStore()
      expect(store.repairDirectoryVisible).toBe(false)
    })
  })

  describe('updateRepairDirectoryRole', () => {
    it('sends {role} and patches the cached options', async () => {
      const store = useProfileStore()
      store.repairDirectory.targetId = 5
      store.repairDirectory.data = {
        current: 0,
        options: [
          { value: 0, selected: true, disabled: false },
          { value: 3, selected: false, disabled: false },
        ],
      }

      mockApi.user.updateRepairDirectoryRole.mockResolvedValueOnce({ data: { role: 3 } })

      await store.updateRepairDirectoryRole(5, 3)

      expect(mockApi.user.updateRepairDirectoryRole).toHaveBeenCalledWith(5, 3)
      expect(store.repairDirectory.data.current).toBe(3)
      expect(store.repairDirectory.data.options.find((o) => o.value === 3).selected).toBe(true)
      expect(store.repairDirectory.data.options.find((o) => o.value === 0).selected).toBe(false)
    })
  })

  describe('fetchAdminSettings / updateAdminSettings', () => {
    it('fetchAdminSettings caches per target id', async () => {
      mockApi.user.getAdminSettings.mockResolvedValueOnce({
        data: { role: 4, assigned_groups: [], preferences: [], permissions: [], roles: [], groups: [], preferences_options: [], permissions_options: [] },
      })

      const store = useProfileStore()
      await store.fetchAdminSettings(9)
      await store.fetchAdminSettings(9)

      expect(mockApi.user.getAdminSettings).toHaveBeenCalledTimes(1)
    })

    it('updateAdminSettings sends the exact legacy payload shape', async () => {
      const store = useProfileStore()
      store.adminSettings.targetId = 9
      store.adminSettings.data = { role: 4 }

      mockApi.user.updateAdminSettings.mockResolvedValueOnce({ data: { role: 2 } })

      await store.updateAdminSettings(9, {
        user_role: 2,
        assigned_groups: [1, 2],
        preferences: [3],
        permissions: [4],
      })

      expect(mockApi.user.updateAdminSettings).toHaveBeenCalledWith(9, {
        user_role: 2,
        assigned_groups: [1, 2],
        preferences: [3],
        permissions: [4],
      })
      expect(store.adminSettings.data.role).toBe(2)
    })
  })

  describe('gap 5 id-scoped family (fetchUserProfile/updateUserProfile/fetchUserSkills/updateUserSkills/fetchUserEmailPreferences/updateUserEmailPreferences/updateUserPassword/deleteUser)', () => {
    it('fetchUserProfile caches per target id, same as fetchAdminSettings', async () => {
      mockApi.user.getUserProfile.mockResolvedValueOnce({
        data: { name: 'Someone', email: 's@example.com', country_code: 'GB', location: '', age: '1990', gender: '', biography: '', countries: [], ages: [] },
      })

      const store = useProfileStore()
      await store.fetchUserProfile(9)

      expect(mockApi.user.getUserProfile).toHaveBeenCalledWith(9)
      expect(store.userProfile.targetId).toBe(9)
      expect(store.userProfile.data.name).toBe('Someone')
    })

    it('updateUserProfile sends the exact legacy field names and merges the response', async () => {
      const store = useProfileStore()
      store.userProfile.targetId = 9
      store.userProfile.data = { name: 'Old', email: 'old@example.com', countries: [{ code: 'GB', name: 'UK' }] }

      mockApi.user.updateUserProfile.mockResolvedValueOnce({
        data: { name: 'New', email: 'new@example.com', country_code: 'GB', location: 'London', age: '1990', gender: '', biography: '' },
      })

      await store.updateUserProfile(9, {
        name: 'New',
        email: 'new@example.com',
        country: 'GB',
        townCity: 'London',
        age: '1990',
        gender: '',
        biography: '',
      })

      expect(mockApi.user.updateUserProfile).toHaveBeenCalledWith(9, {
        name: 'New',
        email: 'new@example.com',
        country: 'GB',
        townCity: 'London',
        age: '1990',
        gender: '',
        biography: '',
      })
      expect(store.userProfile.data.countries).toEqual([{ code: 'GB', name: 'UK' }])
      expect(store.userProfile.data.name).toBe('New')
    })

    it('updateUserSkills sends {tags} and stores the returned selection', async () => {
      const store = useProfileStore()
      store.userSkills.targetId = 9
      store.userSkills.data = { categories: [], selected: [1] }

      mockApi.user.updateUserSkills.mockResolvedValueOnce({ data: { tags: [1, 2] } })

      const data = await store.updateUserSkills(9, { tags: [1, 2] })

      expect(mockApi.user.updateUserSkills).toHaveBeenCalledWith(9, { tags: [1, 2] })
      expect(data.tags).toEqual([1, 2])
      expect(store.userSkills.data.selected).toEqual([1, 2])
    })

    it('fetchUserEmailPreferences populates userEmailPreferences.data', async () => {
      mockApi.user.getUserEmailPreferences.mockResolvedValueOnce({ data: { invites: true } })

      const store = useProfileStore()
      await store.fetchUserEmailPreferences(9)

      expect(mockApi.user.getUserEmailPreferences).toHaveBeenCalledWith(9)
      expect(store.userEmailPreferences.data).toEqual({ invites: true })
    })

    it('updateUserEmailPreferences sends {invites} and stores the response', async () => {
      const store = useProfileStore()
      store.userEmailPreferences.targetId = 9
      store.userEmailPreferences.data = { invites: true }

      mockApi.user.updateUserEmailPreferences.mockResolvedValueOnce({ data: { invites: false } })

      const data = await store.updateUserEmailPreferences(9, { invites: false })

      expect(mockApi.user.updateUserEmailPreferences).toHaveBeenCalledWith(9, { invites: false })
      expect(data).toEqual({ invites: false })
      expect(store.userEmailPreferences.data).toEqual({ invites: false })
    })

    it('updateUserPassword sends the three legacy field names and does not catch errors', async () => {
      mockApi.user.updateUserPassword.mockResolvedValueOnce({ data: { success: true } })

      const store = useProfileStore()
      await store.updateUserPassword(9, {
        current_password: 'old',
        new_password: 'newpass',
        new_password_confirmation: 'newpass',
      })

      expect(mockApi.user.updateUserPassword).toHaveBeenCalledWith(9, {
        current_password: 'old',
        new_password: 'newpass',
        new_password_confirmation: 'newpass',
      })
    })

    it('updateUserPassword rethrows a 422 for the caller to render per-field', async () => {
      const apiError = { status: 422, data: { errors: { current_password: ['Current Password does not match!'] } } }
      mockApi.user.updateUserPassword.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      await expect(
        store.updateUserPassword(9, { current_password: 'wrong', new_password: 'a', new_password_confirmation: 'a' }),
      ).rejects.toEqual(apiError)
    })

    it('deleteUser calls the API and does not touch local auth state', async () => {
      mockApi.user.deleteUser.mockResolvedValueOnce({ data: { success: true } })

      const store = useProfileStore()
      const authStore = useAuthStore()
      authStore.token = 'sometoken'
      authStore.user = { id: 1 }

      const data = await store.deleteUser(9)

      expect(mockApi.user.deleteUser).toHaveBeenCalledWith(9)
      expect(data).toEqual({ success: true })
      expect(authStore.token).toBe('sometoken')
      expect(authStore.user).toEqual({ id: 1 })
    })

    it('deleteUser rethrows without toasting (unlike deleteAccount)', async () => {
      const apiError = { status: 500 }
      mockApi.user.deleteUser.mockRejectedValueOnce(apiError)

      const store = useProfileStore()
      const toastStore = useToastStore()

      await expect(store.deleteUser(9)).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('fetchTargetUser', () => {
    it('populates data on success', async () => {
      mockApi.user.get.mockResolvedValueOnce({ data: { id: 9, name: 'Someone' } })

      const store = useProfileStore()
      const data = await store.fetchTargetUser(9)

      expect(data.name).toBe('Someone')
      expect(store.targetUser.data.name).toBe('Someone')
    })

    it('swallows failure (endpoint not implemented yet) and returns null', async () => {
      mockApi.user.get.mockRejectedValueOnce({ status: 404 })

      const store = useProfileStore()
      const data = await store.fetchTargetUser(9)

      expect(data).toBeNull()
      expect(store.targetUser.error).toBeTruthy()
    })
  })
})
