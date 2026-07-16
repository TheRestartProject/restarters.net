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
// Tab visibility deliberately does NOT match the legacy Blade page 1:1 -
// see stores/profile.js's class doc comment for why the Profile/
// Account(-self-parts)/Email-preferences/Calendars tabs are gated on
// `isOwnProfile` alone here, not `isOwnProfile || isAdmin`. The two
// id-scoped families (Account's admin-settings section, and the standalone
// Repair Directory tab) keep their legacy visibility rules exactly, since
// they are safe to use against someone else's id.
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

const showProfileTab = computed(() => props.isOwnProfile)
const showAccountTab = computed(() => props.isOwnProfile || props.isAdmin)
const showEmailTab = computed(() => props.isOwnProfile)
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
        <ProfileInfoTab />
        <div class="row row-end">
          <div class="col-lg-6">
            <SkillsTab />
          </div>
          <div class="col-lg-6">
            <ProfilePhotoTab />
          </div>
        </div>
      </div>

      <div v-if="showAccountTab" v-show="activeTab === 'account'" data-testid="profile-tab-panel-account">
        <template v-if="isOwnProfile">
          <PasswordTab />
          <LanguageTab />
        </template>
        <AdminSettingsTab v-if="isAdmin" :target-id="targetId" />
        <DeleteAccountTab v-if="isOwnProfile" />
      </div>

      <div v-if="showEmailTab" v-show="activeTab === 'email'" data-testid="profile-tab-panel-email">
        <EmailPreferencesTab />
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
