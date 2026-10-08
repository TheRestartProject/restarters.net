<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationsStore } from '~/stores/notifications.js'
import { useProfileStore } from '~/stores/profile.js'
import { useAuth } from '~/composables/useAuth.js'
import { useRelativeTime } from '~/composables/useRelativeTime.js'

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
const { relativeTime, absoluteDateTime } = useRelativeTime()

// Same "may see Repair Directory settings" proxy as ProfileTabs.vue's
// showRepairDirectoryTab - see stores/profile.js's repairDirectoryVisible
// getter doc comment. Fetched below for the current user (the only person
// this page's sidebar could ever be showing tabs for).
const repairDirectoryVisible = computed(() => profileStore.repairDirectoryVisible)

// Mirrors Fixometer::notificationClasses(): buckets a notification's type
// (class_basename of the Laravel notification class, e.g. 'JoinGroup') into
// the same three card categories so kind stays visually scannable, without
// hardcoding every notification class name here.
const TYPE_CATEGORIES = {
  user: ['AdminNewUser', 'ResetPassword'],
  event: [
    'EventConfirmed',
    'EventDevices',
    'EventRepairs',
    'JoinEvent',
    'AdminModerationEvent',
    'NotifyRestartersOfNewEvent',
    'RSVPEvent',
    'AdminWordPressCreateEventFailure',
    'AdminWordPressEditEventFailure',
  ],
  group: [
    'GroupConfirmed',
    'JoinGroup',
    'AdminModerationGroup',
    'NewGroupMember',
    'NewGroupWithinRadius',
    'AdminWordPressCreateGroupFailure',
    'AdminWordPressEditGroupFailure',
  ],
  device: ['NotifyAdminNoDevices', 'AdminAbnormalDevices'],
}

// Category -> the legacy card__restart/parties/groups/devices icon (see
// Fixometer::notificationClasses). An unmapped type ('other') gets no icon,
// matching the PHP helper returning null there too.
const CATEGORY_ICONS = {
  user: 'restart',
  event: 'parties',
  group: 'groups',
  device: 'devices',
}

function notificationCategory(type) {
  return Object.keys(TYPE_CATEGORIES).find((category) => TYPE_CATEGORIES[category].includes(type)) ?? 'other'
}

function notificationIcon(type) {
  return CATEGORY_ICONS[notificationCategory(type)] || null
}

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

          <div v-if="list.loading && list.items.length === 0" data-testid="notifications-loading" class="placeholder-glow">
            <span class="placeholder col-12 mb-2" style="height: 3rem" />
            <span class="placeholder col-12 mb-2" style="height: 3rem" />
          </div>

          <BAlert v-else-if="list.error" :model-value="true" variant="danger" data-testid="notifications-error">
            {{ t('client.notifications.load_error') }}
          </BAlert>

          <p v-else-if="list.items.length === 0" class="text-muted" data-testid="notifications-empty">
            {{ t('client.notifications.empty') }}
          </p>

          <div v-else class="cards" data-testid="notifications-list">
            <div
              v-for="n in list.items"
              :key="n.id"
              class="card notification-card"
              :class="[notificationIcon(n.type) ? `notification-card--${notificationIcon(n.type)}` : '', { 'notification-card--unread': !n.read }]"
              :data-testid="`notification-${n.id}`"
              :data-notification-category="notificationCategory(n.type)"
            >
              <div class="card-body">
                <h5 class="card-title mb-1">
                  <span v-if="n.title">{{ n.title }}</span>
                  <a v-if="n.url" :href="n.url">{{ n.name }}</a>
                  <span v-else-if="n.name">{{ n.name }}</span>
                </h5>
                <time :title="absoluteDateTime(n.created_at)">{{ relativeTime(n.created_at) }}</time>
                <div class="d-flex justify-content-end mt-1">
                  <!-- Legacy keeps the mark-as-read control in the DOM even
                       once read, toggling it in place to a green-tick
                       confirmation (partials/notification.blade.php's
                       .btn-marked/.marked-as-read pair) rather than removing
                       it entirely. -->
                  <BButton
                    v-if="!n.read"
                    size="sm"
                    variant="link"
                    class="btn-marked"
                    :data-testid="`notification-mark-${n.id}`"
                    @click="markOne(n.id)"
                  >
                    {{ t('notifications.mark_as_read') }}
                  </BButton>
                  <span v-else class="marked-as-read" :data-testid="`notification-marked-${n.id}`">
                    <svg width="13" height="9" viewBox="0 0 54 37" aria-hidden="true">
                      <title>{{ t('notifications.marked_as_read') }}</title>
                      <path
                        d="M4.615 14.064a.969.969 0 0 0-1.334 0l-3 2.979a.868.868 0 0 0 0 1.279l18.334 18c.333.35.916.35 1.291 0l3.042-2.983a.869.869 0 0 0 0-1.28L4.615 14.064z"
                        fill="#0394a6"
                      />
                      <path
                        d="M53.365 4.584a.913.913 0 0 0 .041-1.287L50.365.272c-.334-.358-.959-.363-1.292-.013L15.99 32.109a.873.873 0 0 0 0 1.284l3 3.029a.97.97 0 0 0 1.333.012l33.042-31.85z"
                        fill="#0394a6"
                      />
                    </svg>
                    {{ t('notifications.marked_as_read') }}
                  </span>
                </div>
              </div>
            </div>
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

<style scoped>
/* Category icon + unread-highlight treatment, ported from
   resources/sass/_notifications.scss's `.card`/.status-read rules (that
   partial's naming is inverted: `.status-read` is applied to UNREAD
   notifications and carries the orange highlight - kept the same visual
   result here under more legible class names). */
.notification-card {
  padding: 15px 15px 10px 55px;
  background-repeat: no-repeat;
  background-position: 20px 20px;
  background-size: 24px;
  border-radius: 0;
  border-color: #0394a6;
}

.notification-card--unread {
  background-color: #ffeed7;
}

.notification-card--restart {
  background-image: url('/images/restart.svg');
}

.notification-card--parties {
  background-image: url('/images/parties.svg');
}

.notification-card--groups {
  background-image: url('/images/groups.svg');
}

.notification-card--devices {
  background-image: url('/images/devices.svg');
}

.notification-card .card-body {
  padding: 0;
}

.notification-card time {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.marked-as-read {
  font-size: 0.875rem;
  color: #0394a6;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}
</style>
