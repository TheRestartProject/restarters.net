<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationsStore } from '~/stores/notifications.js'
import { useRelativeTime } from '~/composables/useRelativeTime.js'

// /notifications - the old /profile/notifications page
// (UserController::getNotifications + resources/views/user/notifications.blade
// .php). Lists the user's in-app (Restarters) notifications with a
// mark-as-read affordance, backed by GET /api/v2/users/me/notifications.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('notifications.notifications') })

const store = useNotificationsStore()
const list = computed(() => store.list)
const { relativeTime, absoluteDateTime } = useRelativeTime()

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

function notificationCategory(type) {
  return Object.keys(TYPE_CATEGORIES).find((category) => TYPE_CATEGORIES[category].includes(type)) ?? 'other'
}

function load(page = 1) {
  store.fetchList(page).catch(() => {})
}

onMounted(() => load(1))

async function markAll() {
  await store.markRead(null).catch(() => {})
}

async function markOne(id) {
  await store.markRead(id).catch(() => {})
}
</script>

<template>
  <div class="container py-4" data-testid="notifications-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="mb-0">{{ t('notifications.notifications') }}</h1>
      <BButton
        v-if="list.unread > 0"
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

    <ul v-else class="list-group" data-testid="notifications-list">
      <li
        v-for="n in list.items"
        :key="n.id"
        class="list-group-item"
        :class="[`notification--${notificationCategory(n.type)}`, { 'list-group-item-light': n.read }]"
        :data-testid="`notification-${n.id}`"
        :data-notification-category="notificationCategory(n.type)"
      >
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">
              <span v-if="n.title">{{ n.title }}</span>
              <a v-if="n.url" :href="n.url">{{ n.name }}</a>
              <span v-else-if="n.name">{{ n.name }}</span>
            </div>
            <small class="text-muted" :title="absoluteDateTime(n.created_at)">{{ relativeTime(n.created_at) }}</small>
          </div>
          <BButton
            v-if="!n.read"
            size="sm"
            variant="link"
            :data-testid="`notification-mark-${n.id}`"
            @click="markOne(n.id)"
          >
            {{ t('notifications.mark_as_read') }}
          </BButton>
        </div>
      </li>
    </ul>

    <div v-if="list.lastPage > 1" class="d-flex justify-content-between mt-3">
      <BButton :disabled="list.page <= 1" variant="outline-secondary" data-testid="notifications-prev" @click="load(list.page - 1)">
        {{ t('client.groups.previous_page') }}
      </BButton>
      <span class="align-self-center">{{ list.page }} / {{ list.lastPage }}</span>
      <BButton :disabled="list.page >= list.lastPage" variant="outline-secondary" data-testid="notifications-next" @click="load(list.page + 1)">
        {{ t('client.groups.next_page') }}
      </BButton>
    </div>
  </div>
</template>

<style scoped>
/* Left-edge accent per notificationCategory() bucket, mirroring the
   card__restart/parties/groups/devices classes from
   partials/notification.blade.php - just enough to keep kind scannable at a
   glance without pulling in an icon set. */
.notification--user {
  border-inline-start: 4px solid var(--bs-primary);
}

.notification--event {
  border-inline-start: 4px solid var(--bs-success);
}

.notification--group {
  border-inline-start: 4px solid var(--bs-warning);
}

.notification--device {
  border-inline-start: 4px solid var(--bs-secondary);
}
</style>
