<script setup>
import LoadingPlaceholder from '~/components/LoadingPlaceholder.vue'
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationsStore } from '~/stores/notifications.js'
import { useProfileStore } from '~/stores/profile.js'
import { useAuth } from '~/composables/useAuth.js'
import NotificationCard from '~/components/NotificationCard.vue'

// /notifications - the old /profile/notifications page
// (UserController::getNotifications + resources/views/user/notifications.blade
// .php). Lists the user's in-app (Restarters) notifications with a
// mark-as-read affordance, backed by GET /api/v2/users/me/notifications.
//
// Legacy reuses the full profile-edit chrome around the notification list -
// the 'Profile & Preferences' heading + View Profile button, and the
// list-group tab sidebar down the left (profile-edit.blade.php,
// notifications.blade.php). Reproduced here as this page's own copy of that
// sidebar rather than a shared component with ProfileTabs.vue: every link
// here except "Notifications" itself is a real navigation away to
// /profile/edit, whereas ProfileTabs' nav is local-state tab switching
// within that page - the two have different jobs even though they render
// near-identical markup.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('notifications.notifications') })

const store = useNotificationsStore()
const profileStore = useProfileStore()
const { user } = useAuth()
const list = computed(() => store.list)

// Same "may see Repair Directory settings" proxy as ProfileTabs.vue's
// showRepairDirectoryTab - see stores/profile.js's repairDirectoryVisible
// getter doc comment. Fetched below for the current user (the only person
// this page's sidebar could ever be showing tabs for).
const repairDirectoryVisible = computed(() => profileStore.repairDirectoryVisible)

const NOTIFICATIONS_PER_PAGE = 20

function load(page = 1) {
  store.fetchList(page).catch(() => {})
}

onMounted(() => {
  load(1)

  if (user.value?.id) {
    profileStore.fetchRepairDirectoryOptions(user.value.id).catch(() => {})
  }
})

async function markAll() {
  await store.markRead(null).catch(() => {})
}

async function markOne(id) {
  await store.markRead(id).catch(() => {})
}
</script>

<template>
  <div class="container py-4" data-testid="notifications-page">
    <div class="d-flex align-items-center mb-4">
      <h1 class="mb-0 me-3">{{ t('profile.page_title') }}</h1>
      <NuxtLink to="/profile" class="btn btn-primary ms-auto" data-testid="notifications-view-profile">
        {{ t('profile.view_profile') }}
      </NuxtLink>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-4 offset-lg-sidebar">
        <div class="list-group" data-testid="notifications-sidebar">
          <NuxtLink to="/profile/edit" class="list-group-item list-group-item-action" data-testid="notifications-sidebar-profile">
            {{ t('profile.profile') }}
          </NuxtLink>
          <NuxtLink to="/profile/edit" class="list-group-item list-group-item-action" data-testid="notifications-sidebar-account">
            {{ t('profile.account') }}
          </NuxtLink>
          <NuxtLink to="/profile/edit" class="list-group-item list-group-item-action" data-testid="notifications-sidebar-email">
            {{ t('profile.email_preferences') }}
          </NuxtLink>
          <NuxtLink to="/profile/edit" class="list-group-item list-group-item-action" data-testid="notifications-sidebar-calendars">
            {{ t('profile.calendars.title') }}
          </NuxtLink>
          <span class="list-group-item active" data-testid="notifications-sidebar-active">{{ t('profile.notifications') }}</span>
          <NuxtLink
            v-if="repairDirectoryVisible"
            to="/profile/edit"
            class="list-group-item list-group-item-action"
            data-testid="notifications-sidebar-repair-directory"
          >
            {{ t('profile.repair_directory') }}
          </NuxtLink>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="edit-panel notifications-page">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">{{ t('notifications.notifications') }}</h3>
            <!-- notifications.blade.php:48 renders this unconditionally; ours
                 hid it whenever nothing was unread, so the action vanished
                 exactly when a user might go looking to confirm it had
                 worked. -->
            <BButton
              variant="outline-primary"
              data-testid="notifications-mark-all"
              @click="markAll"
            >
              {{ t('notifications.mark_all_as_read') }}
            </BButton>
          </div>

          <div v-if="list.loading && list.items.length === 0" data-testid="notifications-loading">
            <LoadingPlaceholder height="6rem" />
          </div>

          <BAlert v-else-if="list.error" :model-value="true" variant="danger" data-testid="notifications-error">
            {{ t('client.notifications.load_error') }}
          </BAlert>

          <p v-else-if="list.items.length === 0" class="text-muted" data-testid="notifications-empty">
            {{ t('client.notifications.empty') }}
          </p>

          <div v-else class="cards" data-testid="notifications-list">
            <NotificationCard v-for="n in list.items" :key="n.id" :notification="n" @mark-read="markOne" />
          </div>

          <BPagination
            v-if="list.lastPage > 1"
            :model-value="list.page"
            :total-rows="list.total"
            :per-page="NOTIFICATIONS_PER_PAGE"
            align="center"
            class="mt-3"
            data-testid="notifications-pagination"
            @update:model-value="load"
          />
        </div>
      </div>
    </div>
  </div>
</template>
