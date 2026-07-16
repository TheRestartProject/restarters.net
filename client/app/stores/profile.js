import { defineStore } from 'pinia'
import { useAuthStore } from './auth.js'
import { useSessionStore } from './session.js'
import { useToastStore } from './toast.js'

/**
 * Backs /profile/edit/[[id]] (design.md §6.2 Phase D task brief D1;
 * PR #868's *Tab.vue components + resources/views/user/profile-edit.blade.php
 * + resources/views/user/profile/*.blade.php are the functional spec).
 *
 * users/me/* endpoints (email preferences, calendars, language, profile
 * info, skills, password, photo, delete-account) always operate on
 * Auth::user() - there is no id parameter on any of them (confirmed by
 * reading app/Http/Controllers/API/UserController.php directly). The
 * legacy Blade page nonetheless *shows* the Profile/Account(-self-parts)/
 * Email-preferences/Calendars tabs whenever the viewer is an Administrator
 * OR is editing their own id - meaning an Administrator who opens someone
 * else's /profile/edit/{id} sees those tabs, but saving them silently
 * edits the ADMINISTRATOR's own account, not the target's. That is a
 * pre-existing footgun in PR #868, not a feature: this client deliberately
 * does NOT reproduce it. `docs/nuxt-migration/api-gaps.md` (Phase D)
 * records the deviation. Concretely: components fed by the `me*` actions
 * below (email preferences, calendars, language, profile info, skills,
 * password, photo, delete-account) are only ever shown by ProfileTabs.vue
 * when editing one's own profile; only the two genuinely id-scoped
 * families - repair-directory-* and admin-settings - are shown while
 * editing someone else's.
 */
export const useProfileStore = defineStore('profile', {
  state: () => ({
    emailPreferences: { data: null, loading: false, error: null },
    calendars: { data: null, loading: false, error: null },
    language: { data: null, loading: false, error: null },
    info: { data: null, loading: false, error: null },
    skills: { data: null, loading: false, error: null },

    // Best-effort GET /api/v2/users/{id} for the "Editing <name>" heading
    // when viewing someone else's profile. NOT YET IMPLEMENTED
    // server-side (that's design.md §6.2 Phase D task D2) - see
    // docs/nuxt-migration/api-gaps.md. Fails silently; the page falls back
    // to a generic "Editing user #{id}" heading.
    targetUser: { data: null, loading: false, error: null },

    // Keyed by target id (not "me" - these two families are legitimately
    // id-scoped), so switching between editing different users' profiles
    // (e.g. an Administrator browsing /user/all) re-fetches rather than
    // showing stale data.
    repairDirectory: { targetId: null, data: null, loading: false, error: null },
    adminSettings: { targetId: null, data: null, loading: false, error: null },
  }),

  getters: {
    // "Can the acting user change ANY Repair Directory role via this
    // endpoint" is exactly Policy::viewRepairDirectorySettings(Auth::user())
    // in disguise: UserPolicy::changeRepairDirRole only ever returns true
    // for a Regional/Super Admin (see app/Policies/UserPolicy.php), so "at
    // least one option isn't disabled" is a reliable, id-independent proxy
    // for "the acting user may see this tab at all" - the same call the
    // legacy Blade page makes via `@can('viewRepairDirectorySettings', ...)`
    // (which checks the *acting* user, not the target). Used both to gate
    // the Repair Directory tab's visibility and, for a viewer who is
    // neither the profile owner nor an Administrator, whether they may see
    // this page at all (mirrors UserController::getProfileEdit's 404 guard).
    repairDirectoryVisible: (state) => {
      const options = state.repairDirectory.data?.options
      return Array.isArray(options) && options.some((option) => !option.disabled)
    },
  },

  actions: {
    async fetchEmailPreferences() {
      const { $api } = useNuxtApp()
      this.emailPreferences.loading = true
      this.emailPreferences.error = null

      try {
        const { data } = await $api.user.getEmailPreferences()
        this.emailPreferences.data = data
        return data
      } catch (error) {
        this.emailPreferences.error = error
        throw error
      } finally {
        this.emailPreferences.loading = false
      }
    },

    // Not caught/toasted: EmailPreferencesTab.vue renders its own inline
    // feedback (mirrors the legacy tab's b-alert), same convention as
    // GroupForm.vue's field-error rendering.
    async updateEmailPreferences(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateEmailPreferences(payload)
      this.emailPreferences.data = data
      return data
    },

    async fetchCalendars() {
      const { $api } = useNuxtApp()
      this.calendars.loading = true
      this.calendars.error = null

      try {
        const { data } = await $api.user.getCalendars()
        this.calendars.data = data
        return data
      } catch (error) {
        this.calendars.error = error
        throw error
      } finally {
        this.calendars.loading = false
      }
    },

    async fetchLanguage() {
      const { $api } = useNuxtApp()
      this.language.loading = true
      this.language.error = null

      try {
        const { data } = await $api.user.getLanguage()
        this.language.data = data
        return data
      } catch (error) {
        this.language.error = error
        throw error
      } finally {
        this.language.loading = false
      }
    },

    async updateLanguage(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateLanguage(payload)

      if (this.language.data) {
        this.language.data = { ...this.language.data, language: data.language }
      }

      // Keep the session store (navbar/LocaleSwitcher, when it lands in
      // Phase E) in step immediately, rather than waiting for a full
      // GET /api/v2/session round-trip.
      const sessionStore = useSessionStore()
      if (sessionStore.user) {
        sessionStore.user = { ...sessionStore.user, language: data.language }
      }

      return data
    },

    async fetchProfileInfo() {
      const { $api } = useNuxtApp()
      this.info.loading = true
      this.info.error = null

      try {
        const { data } = await $api.user.getProfileInfo()
        this.info.data = data
        return data
      } catch (error) {
        this.info.error = error
        throw error
      } finally {
        this.info.loading = false
      }
    },

    async updateProfileInfo(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateProfileInfo(payload)

      // Preserve the countries/ages option lists the initial GET carried -
      // updateMyProfilev2's response doesn't repeat them.
      this.info.data = { ...this.info.data, ...data }

      const sessionStore = useSessionStore()
      if (sessionStore.user) {
        sessionStore.user = { ...sessionStore.user, name: data.name, email: data.email }
      }

      return data
    },

    async fetchSkills() {
      const { $api } = useNuxtApp()
      this.skills.loading = true
      this.skills.error = null

      try {
        const { data } = await $api.user.getSkills()
        this.skills.data = data
        return data
      } catch (error) {
        this.skills.error = error
        throw error
      } finally {
        this.skills.loading = false
      }
    },

    async updateSkills(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateSkills(payload)
      if (this.skills.data) {
        this.skills.data = { ...this.skills.data, selected: data.tags }
      }
      return data
    },

    // Not caught: PasswordTab.vue renders the 422 (current_password /
    // new_password_confirmation) inline per-field, same convention as
    // GroupForm.vue.
    async updatePassword(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updatePassword(payload)
      return data
    },

    // POST /api/v2/users/me/photo {upload_key} - called with the key
    // TusImageUpload.vue emits once its tus upload completes. Mirrors
    // ProfilePhotoTab.vue: builds the thumbnail URL from the returned bare
    // filename and pushes it onto the session store immediately, so the
    // navbar avatar and this tab's preview update without waiting for a
    // fresh GET /api/v2/session.
    async uploadPhoto(uploadKey) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updatePhoto(uploadKey)

      const sessionStore = useSessionStore()
      if (sessionStore.user && data?.path) {
        const runtimeConfig = useRuntimeConfig()
        sessionStore.user = {
          ...sessionStore.user,
          avatar_url: `${runtimeConfig.public.apiBase}/uploads/thumbnail_${data.path}`,
        }
      }

      return data
    },

    // DELETE /api/v2/users/me. Clears local auth state on success (mirrors
    // the legacy tab's `window.location = '/login'` - the account is gone,
    // there is nothing left to keep signed into).
    async deleteAccount() {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.user.deleteAccount()
        useAuthStore().clear()
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // GET /api/v2/users/{id}/repair-directory-options. Cached per target
    // id (see the class doc comment); pass force:true after a save that
    // might change the caller's own repair-directory role and so its
    // *own* editable-options set.
    async fetchRepairDirectoryOptions(id, { force = false } = {}) {
      if (!force && this.repairDirectory.targetId === id && this.repairDirectory.data) {
        return this.repairDirectory.data
      }

      const { $api } = useNuxtApp()
      this.repairDirectory.loading = true
      this.repairDirectory.error = null

      try {
        const { data } = await $api.user.getRepairDirectoryOptions(id)
        this.repairDirectory.targetId = id
        this.repairDirectory.data = data
        return data
      } catch (error) {
        this.repairDirectory.targetId = id
        this.repairDirectory.data = null
        this.repairDirectory.error = error
        throw error
      } finally {
        this.repairDirectory.loading = false
      }
    },

    async updateRepairDirectoryRole(id, role) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateRepairDirectoryRole(id, role)

      if (this.repairDirectory.targetId === id && this.repairDirectory.data) {
        this.repairDirectory.data = {
          ...this.repairDirectory.data,
          current: data.role,
          options: this.repairDirectory.data.options.map((option) => ({
            ...option,
            selected: option.value === data.role,
          })),
        }
      }

      return data
    },

    async fetchAdminSettings(id, { force = false } = {}) {
      if (!force && this.adminSettings.targetId === id && this.adminSettings.data) {
        return this.adminSettings.data
      }

      const { $api } = useNuxtApp()
      this.adminSettings.loading = true
      this.adminSettings.error = null

      try {
        const { data } = await $api.user.getAdminSettings(id)
        this.adminSettings.targetId = id
        this.adminSettings.data = data
        return data
      } catch (error) {
        this.adminSettings.targetId = id
        this.adminSettings.data = null
        this.adminSettings.error = error
        throw error
      } finally {
        this.adminSettings.loading = false
      }
    },

    async updateAdminSettings(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.updateAdminSettings(id, payload)

      if (this.adminSettings.targetId === id && this.adminSettings.data) {
        this.adminSettings.data = { ...this.adminSettings.data, role: data.role }
      }

      return data
    },

    // Best-effort - see the state doc comment. Swallows failure (including
    // the 404 this endpoint returns today, since it doesn't exist yet)
    // rather than blocking the page: the heading falls back to a generic
    // "Editing user #{id}".
    async fetchTargetUser(id) {
      const { $api } = useNuxtApp()
      this.targetUser.loading = true
      this.targetUser.error = null
      this.targetUser.data = null

      try {
        const { data } = await $api.user.get(id)
        this.targetUser.data = data
        return data
      } catch (error) {
        this.targetUser.error = error
        return null
      } finally {
        this.targetUser.loading = false
      }
    },
  },
})
