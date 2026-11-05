<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import ProfileInfoTab from './ProfileInfoTab.vue'
import SkillsTab from './SkillsTab.vue'
import ProfilePhotoTab from './ProfilePhotoTab.vue'
import PasswordTab from './PasswordTab.vue'
import LanguageTab from './LanguageTab.vue'
import AdminSettingsTab from './AdminSettingsTab.vue'
import DeleteAccountTab from './DeleteAccountTab.vue'
import EmailPreferencesTab from './EmailPreferencesTab.vue'
import CalendarsTab from './CalendarsTab.vue'
import RepairDirectoryTab from './RepairDirectoryTab.vue'

// Tab-nav + panels for /profile/edit/[[id]] (design.md §6.2 Phase D task
// D1). Functional spec: resources/views/user/profile-edit.blade.php's
// list-group tab nav + resources/views/user/profile/*.blade.php's five
// tab-content panels (a sixth, "Notifications", is a plain external nav
// link, not a panel - it always routes to /notifications).
//
// Tab visibility now matches the legacy Blade page far more closely than
// it used to (full-parity gap 5 - see stores/profile.js's class doc
// comment for the id-scoped `/users/{id}/profile|skills|preferences|
// password` + DELETE `/users/{id}` endpoints this closes the gap with):
// Profile/Account/Email are gated on `isOwnProfile || isAdmin`, same as
// Account already was. Calendars and the Notifications link stay
// self-only - no id-scoped counterpart exists for either - and so does
// ProfilePhotoTab within the Profile panel and LanguageTab within the
// Account panel (see below). Every component fed by an id-scoped call
// takes `targetId`/`isOwnProfile` props and picks `/me/*` vs `/{id}/*`
// itself; the two families that were ALREADY genuinely id-scoped before
// this (Account's admin-settings section, and the standalone Repair
// Directory tab) are unaffected.
const props = defineProps({
  targetId: {
    type: Number,
    required: true,
  },
  isOwnProfile: {
    type: Boolean,
    default: false,
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()

const showProfileTab = computed(() => props.isOwnProfile || props.isAdmin)
const showAccountTab = computed(() => props.isOwnProfile || props.isAdmin)
const showEmailTab = computed(() => props.isOwnProfile || props.isAdmin)
const showCalendarsTab = computed(() => props.isOwnProfile)
const showNotificationsLink = computed(() => props.isOwnProfile)
// See stores/profile.js's repairDirectoryVisible getter doc comment: a
// reliable, id-independent proxy for Policy::viewRepairDirectorySettings.
const showRepairDirectoryTab = computed(() => profileStore.repairDirectoryVisible)

const visibleTabIds = computed(() => {
  const ids = []
  if (showProfileTab.value) ids.push('profile')
  if (showAccountTab.value) ids.push('account')
  if (showEmailTab.value) ids.push('email')
  if (showCalendarsTab.value) ids.push('calendars')
  if (showRepairDirectoryTab.value) ids.push('repair-directory')
  return ids
})

const activeTab = ref(null)

// Keeps activeTab pointed at a tab that's actually visible - handles both
// the synchronous case (isOwnProfile/isAdmin known immediately) and the
// async one (repairDirectoryVisible only resolves once the GET .../
// repair-directory-options the page kicked off settles - see
// RepairDirectoryTab.vue's doc comment).
watch(
  visibleTabIds,
  (ids) => {
    if (!activeTab.value || !ids.includes(activeTab.value)) {
      activeTab.value = ids[0] || null
    }
  },
  { immediate: true },
)
</script>

<template>
  <div class="row" data-testid="profile-tabs">
    <div class="col-lg-4 col-xl-3">
      <div class="list-group" data-testid="profile-tabs-nav">
        <button
          v-if="showProfileTab"
          type="button"
          class="list-group-item list-group-item-action"
          :class="{ active: activeTab === 'profile' }"
          data-testid="profile-tab-nav-profile"
          @click="activeTab = 'profile'"
        >
          {{ t('profile.profile') }}
        </button>
        <button
          v-if="showAccountTab"
          type="button"
          class="list-group-item list-group-item-action"
          :class="{ active: activeTab === 'account' }"
          data-testid="profile-tab-nav-account"
          @click="activeTab = 'account'"
        >
          {{ t('profile.account') }}
        </button>
        <button
          v-if="showEmailTab"
          type="button"
          class="list-group-item list-group-item-action"
          :class="{ active: activeTab === 'email' }"
          data-testid="profile-tab-nav-email"
          @click="activeTab = 'email'"
        >
          {{ t('profile.email_preferences') }}
        </button>
        <button
          v-if="showCalendarsTab"
          type="button"
          class="list-group-item list-group-item-action"
          :class="{ active: activeTab === 'calendars' }"
          data-testid="profile-tab-nav-calendars"
          @click="activeTab = 'calendars'"
        >
          {{ t('profile.calendars.title') }}
        </button>
        <NuxtLink v-if="showNotificationsLink" to="/notifications" class="list-group-item list-group-item-action" data-testid="profile-tab-nav-notifications">
          {{ t('profile.notifications') }}
        </NuxtLink>
        <button
          v-if="showRepairDirectoryTab"
          type="button"
          class="list-group-item list-group-item-action"
          :class="{ active: activeTab === 'repair-directory' }"
          data-testid="profile-tab-nav-repair-directory"
          @click="activeTab = 'repair-directory'"
        >
          {{ t('profile.repair_directory') }}
        </button>
      </div>
    </div>

    <div class="col-lg-8 col-xl-9">
      <div v-if="showProfileTab" v-show="activeTab === 'profile'" data-testid="profile-tab-panel-profile">
        <ProfileInfoTab :target-id="targetId" :is-own-profile="isOwnProfile" />
        <div class="row row-end">
          <div class="col-lg-6 d-flex col-bottom">
            <SkillsTab :target-id="targetId" :is-own-profile="isOwnProfile" />
          </div>
          <div class="col-lg-6 d-flex col-bottom">
            <!-- No id-scoped photo-upload endpoint - self-only, see the
                 class doc comment above. -->
            <ProfilePhotoTab v-if="isOwnProfile" />
          </div>
        </div>
      </div>

      <div v-if="showAccountTab" v-show="activeTab === 'account'" data-testid="profile-tab-panel-account">
        <PasswordTab :target-id="targetId" :is-own-profile="isOwnProfile" />
        <!-- No id-scoped language endpoint - self-only, see the class doc
             comment above. -->
        <LanguageTab v-if="isOwnProfile" />
        <AdminSettingsTab v-if="isAdmin" :target-id="targetId" />
        <DeleteAccountTab :target-id="targetId" :is-own-profile="isOwnProfile" />
      </div>

      <div v-if="showEmailTab" v-show="activeTab === 'email'" data-testid="profile-tab-panel-email">
        <EmailPreferencesTab :target-id="targetId" :is-own-profile="isOwnProfile" />
      </div>

      <div v-if="showCalendarsTab" v-show="activeTab === 'calendars'" data-testid="profile-tab-panel-calendars">
        <CalendarsTab />
      </div>

      <div v-if="showRepairDirectoryTab" v-show="activeTab === 'repair-directory'" data-testid="profile-tab-panel-repair-directory">
        <RepairDirectoryTab :target-id="targetId" />
      </div>
    </div>
  </div>
</template>

<style>
/* Unscoped deliberately: every panel component this page mounts
   (ProfileInfoTab/SkillsTab/ProfilePhotoTab/PasswordTab/LanguageTab/
   AdminSettingsTab/EmailPreferencesTab/CalendarsTab/RepairDirectoryTab/
   DeleteAccountTab) renders its own root `.edit-panel` div, so the class
   needs to be visible across all of those separate SFCs rather than
   scoped to this one. Ported from legacy resources/sass/_edit.scss's
   `.edit-panel` (values converted from that partial's Fixometer-only
   $brand/$brand-black, which are both #222 there - not the teal $brand
   used elsewhere in this app), including its `label` rule - every one of
   this branch's pre-Phase-F profile tab components (git show
   07e6abd7cc^:resources/js/components/DeleteAccountTab.vue etc.) wrapped
   in `.edit-panel` too, DeleteAccountTab included - it's only
   origin/develop's older, since-superseded Blade that leaves it
   unwrapped (docs/nuxt-migration/findings/parity-v2/profile-notifs-auth.md
   gap 8 was diffed against that stale baseline). */
.edit-panel {
  background-color: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px #222;
  padding: 20px;
  margin: 0 0 30px 0;
}

.edit-panel label {
  font-size: 16px;
  font-weight: 700;
}

@media (min-width: 992px) {
  .edit-panel {
    padding: 30px;
  }
}
</style>
