<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useAuthStore } from '~/stores/auth.js'
import { useNotificationsStore } from '~/stores/notifications.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
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
      <!-- resources/js/components/Notifications.vue's inline fa-comments
           glyph (viewBox 0 0 576 512, filled twin speech-bubble) - not
           IconTalk.vue, which is the nav bar's own single-bubble outline
           icon and never appeared on this badge in develop. -->
      <svg width="22" height="20" aria-hidden="true" viewBox="0 0 576 512" focusable="false">
        <path fill="currentColor" d="M416 192c0-88.4-93.1-160-208-160S0 103.6 0 192c0 34.3 14.1 65.9 38 92-13.4 30.2-35.5 54.2-35.8 54.5-2.2 2.3-2.8 5.7-1.5 8.7S4.8 352 8 352c36.6 0 66.9-12.3 88.7-25 32.2 15.7 70.3 25 111.3 25 114.9 0 208-71.6 208-160zm122 220c23.9-26 38-57.7 38-92 0-66.9-53.5-124.2-129.3-148.1.9 6.6 1.3 13.3 1.3 20.1 0 105.9-107.7 192-240 192-10.8 0-21.3-.8-31.7-1.9C207.8 439.6 281.8 480 368 480c41 0 79.1-9.2 111.3-25 21.8 12.7 52.1 25 88.7 25 3.2 0 6.1-1.9 7.3-4.8 1.3-2.9.7-6.3-1.5-8.7-.3-.3-22.4-24.2-35.8-54.5z"/>
      </svg>
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

// Ported from develop's actual rendered colours, not the plain
// Bootstrap-4 `.badge-info`/`.badge-pill` classes Notifications.vue's own
// template binds: those two badges sit inside `<nav class="nav-wrapper">`
// (layouts/navbar.blade.php), and _navigation.scss's `.nav-wrapper .badge`
// rule (2 classes) outweighs `.badge-info` (1 class) regardless of import
// order, so both badges render orange/white while they have a count, not
// teal. `.badge-group .badge-no-notifications` (_notifications.scss,
// equal specificity, loaded after _navigation.scss) then wins the empty
// state's grey/white - and its own :hover rule similarly beats
// `.nav-wrapper .badge:hover`'s darker orange for that state only.
.notification-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  border: none;
  border-radius: 10rem;
  padding: 0.25em 0.75em;
  font-size: 16px;
  font-weight: 400;
  color: #fff;
  background-color: #ec7f00;

  &:hover,
  &:focus,
  &:active {
    background-color: #af6713;
    color: #fff;
  }

  &--empty {
    background-color: #ccc;

    &:hover,
    &:focus,
    &:active {
      background-color: #7e7e7e;
    }
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
