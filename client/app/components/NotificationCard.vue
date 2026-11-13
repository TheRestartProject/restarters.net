<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRelativeTime } from '~/composables/useRelativeTime.js'

// One notification card, shared by the /notifications page and the navbar
// bell panel (live renders both from partials/notification.blade.php).
const props = defineProps({
  notification: { type: Object, required: true },
})
const emit = defineEmits(['mark-read'])

const { t } = useI18n()
const { relativeTime, absoluteDateTime } = useRelativeTime()

// Mirrors Fixometer::notificationClasses(): buckets a notification's type
// (class_basename of the Laravel notification class, e.g. 'JoinGroup') into
// the same card categories so kind stays visually scannable, without
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

// Category -> the legacy card__restart/parties/groups/devices icon. An
// unmapped type ('other') gets no icon, matching the PHP helper.
const CATEGORY_ICONS = {
  user: 'restart',
  event: 'parties',
  group: 'groups',
  device: 'devices',
}

const category = computed(
  () => Object.keys(TYPE_CATEGORIES).find((c) => TYPE_CATEGORIES[c].includes(props.notification.type)) ?? 'other'
)
const icon = computed(() => CATEGORY_ICONS[category.value] || null)
</script>

<template>
  <div
    class="card notification-card"
    :class="[icon ? `notification-card--${icon}` : '', { 'notification-card--unread': !notification.read }]"
    :data-testid="`notification-${notification.id}`"
    :data-notification-category="category"
  >
    <div class="card-body">
      <h5 class="card-title mb-1">
        <!-- Live's template puts whitespace between the title and the link. -->
        <span v-if="notification.title">{{ notification.title }}</span>
        {{ notification.title && notification.url ? ' ' : '' }}
        <a v-if="notification.url" :href="notification.url">{{ notification.name }}</a>
        <span v-else-if="notification.name">{{ notification.name }}</span>
      </h5>
      <time :title="absoluteDateTime(notification.created_at)">{{ relativeTime(notification.created_at) }}</time>
      <div class="d-flex justify-content-end mt-1">
        <!-- Legacy keeps the mark-as-read control in the DOM even once read,
             toggling it in place to a green-tick confirmation
             (.btn-marked/.marked-as-read) rather than removing it. -->
        <BButton
          v-if="!notification.read"
          size="sm"
          variant="link"
          class="btn-marked"
          :data-testid="`notification-mark-${notification.id}`"
          @click="emit('mark-read', notification.id)"
        >
          {{ t('notifications.mark_as_read') }}
        </BButton>
        <span v-else class="marked-as-read" :data-testid="`notification-marked-${notification.id}`">
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
