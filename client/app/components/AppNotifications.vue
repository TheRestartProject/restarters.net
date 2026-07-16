<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useAuthStore } from '~/stores/auth.js'
import { useNotificationsStore } from '~/stores/notifications.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
import IconTalk from './icons/IconTalk.vue'
import IconBell from './icons/IconBell.vue'

// Port of resources/js/components/Notifications.vue: two badges (Talk
// unread count via Discourse, Restarters unread count), both sourced from
// GET /api/users/{id}/notifications (stores/notifications.js). The legacy
// widget delayed its single fetch by 5s to keep it off the critical page
// load and never repeated it; that constraint doesn't apply to an SPA nav
// bar, so this fetches immediately on mount and then polls, matching the
// "count polling" requirement of this migration slice.
const POLL_MS = 60000

const { t } = useI18n()
const sessionStore = useSessionStore()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const { goTo } = useSsoBridge()

const open = ref(false)
let timer = null

const restarters = computed(() => notificationsStore.restarters)
const discourse = computed(() => notificationsStore.discourse)

// Mirrors Notifications.vue's padCount(): unknown -> '--', capped at 99.
function pad(value) {
  if (value === null || value === undefined) {
    return '--'
  }

  return value > 99 ? '99+' : String(value)
}

function toggleRestarters() {
  open.value = !open.value
}

// Mirrors the legacy widget's goto(): DISCOURSE_URL + '/session/sso?
// return_path=' + DISCOURSE_URL + '/u/' + username + '/notifications' -
// but routed through the SSO bridge (design.md §4.3, useSsoBridge) since
// the SPA carries no Laravel web session for Discourse to authenticate
// against directly.
function goToDiscourse() {
  const base = sessionStore.config?.discourse_url
  if (!base) {
    return
  }

  const username = authStore.user?.username
  const returnPath = username ? `${base}/u/${username}/notifications` : base

  goTo(`${base}/session/sso?return_path=${encodeURIComponent(returnPath)}`)
}

onMounted(() => {
  notificationsStore.fetch()
  timer = setInterval(() => notificationsStore.fetch(), POLL_MS)
})

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
})
</script>

<template>
  <div class="notifications-badges" data-testid="app-notifications">
    <button
      type="button"
      class="notification-badge"
      :class="{ 'notification-badge--empty': !discourse }"
      data-testid="notifications-discourse-badge"
      :aria-label="t('client.notifications.discourse_aria')"
      @click="goToDiscourse"
    >
      <IconTalk />
      <span class="notification-badge__count" data-testid="notifications-discourse-count">{{ pad(discourse) }}</span>
    </button>

    <button
      type="button"
      class="notification-badge"
      :class="{ 'notification-badge--empty': !restarters }"
      data-testid="notifications-restarters-badge"
      :aria-expanded="open"
      :aria-label="t('client.notifications.restarters_aria')"
      @click="toggleRestarters"
    >
      <IconBell />
      <span class="notification-badge__count" data-testid="notifications-restarters-count">{{ pad(restarters) }}</span>
    </button>

    <div v-if="open" class="notifications-panel" data-testid="notifications-restarters-panel">
      <p v-if="restarters" data-testid="notifications-restarters-text">
        {{ t('client.notifications.unread', { count: restarters }, restarters) }}
      </p>
      <p v-else data-testid="notifications-restarters-empty">
        {{ t('general.alert_uptodate') }}
      </p>
    </div>
  </div>
</template>

<style scoped lang="scss">
.notifications-badges {
  position: relative;
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.notification-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  border: none;
  background: transparent;
  color: inherit;
  padding: 0;

  &--empty .notification-badge__count {
    opacity: 0.5;
  }
}

.notifications-panel {
  position: absolute;
  top: 100%;
  right: 0;
  min-width: 220px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0.25rem;
  padding: 0.75rem 1rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  z-index: 20;
  margin-top: 0.5rem;

  p {
    margin: 0;
  }
}
</style>
