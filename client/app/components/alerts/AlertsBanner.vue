<script setup>
import { computed, onMounted, ref } from 'vue'
import moment from 'moment'

// Admin-configured, dismissible informational banners shown above the
// events list. Functional spec: resources/views/events/index.blade.php ->
// resources/js/components/GroupEvents.vue's `<AlertBanner v-if="banner" />`
// -> resources/js/components/AlertBanner.vue (Vuex alerts/fetch + alerts/
// list). Backed by AlertsAPI.list() (GET /api/v2/alerts).
//
// GET /api/v2/alerts already filters to currently-active alerts server-side,
// but the response is cached for 2h (AlertController::listAlertsv2) - an
// alert whose `end` has just passed can still come back from that stale
// cache, which is exactly why the legacy component re-checked start/end
// client-side. Reproduced here with the same moment() comparison.
//
// Dismissal is NOT a server call: there is no per-user dismiss endpoint (the
// only PUT/PATCH on this resource are Administrator-only create/edit
// actions - see AlertsAPI.js). Legacy AlertBanner.vue dismissed purely via
// localStorage (`alert-<id>`), reproduced below.
const alerts = ref([])

const activeAlerts = computed(() =>
  alerts.value.filter((alert) => moment().isSameOrAfter(alert.start) && moment().isSameOrBefore(alert.end)),
)

const visibleAlerts = computed(() =>
  activeAlerts.value.filter((alert) => {
    try {
      return !localStorage.getItem(`alert-${alert.id}`)
    } catch {
      return true
    }
  }),
)

onMounted(async () => {
  try {
    const { $api } = useNuxtApp()
    const { data } = await $api.alerts.list()
    alerts.value = data || []
  } catch {
    // Degrade to no banner, same as legacy's fetch failure handling.
  }
})

function dismiss(id) {
  try {
    localStorage.setItem(`alert-${id}`, true)
  } catch {
    // Storage unavailable (e.g. private browsing) - the alert just
    // reappears next visit, matching the legacy fallback.
  }

  // localStorage isn't reactive, so drop the alert from the source list
  // directly rather than relying on visibleAlerts to notice.
  alerts.value = alerts.value.filter((alert) => alert.id !== id)
}
</script>

<template>
  <div v-if="visibleAlerts.length" data-testid="alerts-banner">
    <!-- Legacy AlertBanner.vue binds `:variant="alert.variant"` then
         immediately repeats a static `variant="secondary"` on the same
         b-alert tag - a Vue 2 duplicate-attribute bug where the later
         (static) one always wins, so every alert renders grey regardless
         of its real variant. Reproduced verbatim: `variant` is always
         'secondary' here too, not `alert.variant || 'secondary'`. -->
    <BAlert
      v-for="alert in visibleAlerts"
      :key="alert.id"
      :model-value="true"
      variant="secondary"
      dismissible
      class="mb-2"
      :data-testid="`alert-banner-${alert.id}`"
      @dismissed="dismiss(alert.id)"
    >
      <div class="d-sm-flex flex-row justify-content-between align-items-center">
        <div>
          <strong class="d-block mb-2">{{ alert.title }}</strong>
          <!-- eslint-disable-next-line vue/no-v-html -- admin-authored alert body -->
          <div v-html="alert.html" />
        </div>

        <div v-if="alert.ctatitle && alert.ctalink" class="mt-3 mt-sm-0 ms-sm-3">
          <a :href="alert.ctalink" class="btn btn-primary" target="_blank" rel="noopener">{{ alert.ctatitle }}</a>
        </div>
      </div>
    </BAlert>
  </div>
</template>
