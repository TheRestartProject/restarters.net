<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import { useGroupsStore } from '~/stores/groups.js'
import { useAuth } from '~/composables/useAuth.js'
import EventForm from '~/components/events/EventForm.vue'

// /party/duplicate/[id] - PartyController::duplicate() renders the same
// events.create Blade view with a `duplicateFrom` prop (design.md §6.2
// section 4 C4 task brief) - so, like the legacy component, this page is
// "the create form, prefilled from an existing event" rather than its own
// distinct flow. GET /api/v2/events/{id} is public/unauthenticated
// (EventController::getEventv2 has no permission check at all), so the
// forbidden gate below is this client's own approximation of
// PartyController::duplicate()'s `userHasEditPartyPermission` check - same
// safe-false-negative pattern as pages/party/edit/[id].vue (see there for
// the full rationale).
definePageMeta({ auth: true })

const HOST_ROLE = 3

const { t } = useI18n()
const route = useRoute()
const eventsStore = useEventsStore()
const dashboardStore = useDashboardStore()
const groupsStore = useGroupsStore()
const { hasRole } = useAuth()

const sourceId = computed(() => Number(route.params.id))
const sourceEvent = computed(() => eventsStore.current.data)

const isAdmin = computed(() => hasRole('Administrator'))
const hostedGroupIds = computed(() =>
  (dashboardStore.data?.your_groups || []).filter((g) => g.role === HOST_ROLE).map((g) => g.id)
)
const canDuplicate = computed(
  () => isAdmin.value || (!!sourceEvent.value?.group && hostedGroupIds.value.includes(sourceEvent.value.group.id))
)

const groupOptions = ref([])
const groupsLoading = ref(true)

useHead({ title: t('events.duplicate_event') })

async function load() {
  groupsLoading.value = true

  const eventPromise = eventsStore.fetchEvent(sourceId.value)
  dashboardStore.fetch().catch(() => {})

  try {
    if (isAdmin.value) {
      await groupsStore.fetchNames()
      groupOptions.value = groupsStore.names
        .filter((g) => !g.archived_at)
        .map((g) => ({ id: g.id, name: g.name }))
    } else {
      const { $api } = useNuxtApp()
      const { data } = await $api.user.myGroups()
      groupOptions.value = (data || [])
        .filter((g) => g.role === HOST_ROLE && !g.archived)
        .map((g) => ({ id: g.id, name: g.name }))
    }
  } catch {
    groupOptions.value = []
  }

  await eventPromise.catch(() => {})
  groupsLoading.value = false
}

onMounted(load)

function retry() {
  load()
}

// Same post-create navigation choice as pages/party/create/[[group_id]].vue
// - see that file's onCreated comment for the full rationale.
async function onCreated(id) {
  await navigateTo(`/party/edit/${id}`)
}
</script>

<template>
  <div class="container py-4" data-testid="party-duplicate-page">
    <h1>{{ t('events.duplicate_event') }}</h1>

    <div v-if="groupsLoading || eventsStore.current.loading" data-testid="party-duplicate-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="eventsStore.current.error"
      :model-value="true"
      variant="danger"
      data-testid="party-duplicate-error"
    >
      <p>{{ t('client.events.duplicate_load_error') }}</p>
      <BButton variant="danger" data-testid="party-duplicate-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <BAlert
      v-else-if="sourceEvent && !canDuplicate"
      :model-value="true"
      variant="danger"
      data-testid="party-duplicate-forbidden"
    >
      {{ t('client.events.edit_forbidden') }}
    </BAlert>

    <EventForm
      v-else-if="sourceEvent"
      :groups="groupOptions"
      :is-admin="isAdmin"
      :initial-event="sourceEvent"
      :is-duplicate="true"
      @created="onCreated"
    />
  </div>
</template>
