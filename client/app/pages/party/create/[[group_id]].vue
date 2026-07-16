<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useGroupsStore } from '~/stores/groups.js'
import EventForm from '~/components/events/EventForm.vue'

// /party/create/[[group_id]] - resources/views/events/create.blade.php +
// EventAddEditPage.vue (design.md §6.2 section 4 C4 task brief). The
// optional `group_id` param mirrors PartyController::create($request,
// $group_id = null) - a preselected/preapproved host group when arriving
// via a group page's "add an event" link.
definePageMeta({ auth: true })

const HOST_ROLE = 3

const { t } = useI18n()
const route = useRoute()
const { hasRole } = useAuth()
const groupsStore = useGroupsStore()

const initialGroupId = computed(() => (route.params.group_id ? Number(route.params.group_id) : null))
const isAdmin = computed(() => hasRole('Administrator'))

const groupOptions = ref([])
const groupsLoading = ref(true)
const groupsError = ref(false)

useHead({ title: t('events.add_new_event') })

async function load() {
  groupsLoading.value = true
  groupsError.value = false

  try {
    if (isAdmin.value) {
      // PartyController::create passes every group to an Administrator
      // ($allGroups = Group::orderBy('name')->get()).
      await groupsStore.fetchNames()
      groupOptions.value = groupsStore.names
        .filter((g) => !g.archived_at)
        .map((g) => ({ id: g.id, name: g.name }))
    } else {
      // Everyone else gets $user->groupsInChargeOf()'s base case: groups
      // they host directly (users_groups.role === HOST). The
      // NetworkCoordinator branch of groupsInChargeOf() (every group in a
      // network they coordinate) can't be replicated client-side with
      // today's API - see docs/nuxt-migration/api-gaps.md Phase C.
      const { $api } = useNuxtApp()
      const { data } = await $api.user.myGroups()
      groupOptions.value = (data || [])
        .filter((g) => g.role === HOST_ROLE && !g.archived)
        .map((g) => ({ id: g.id, name: g.name }))
    }
  } catch {
    groupsError.value = true
  } finally {
    groupsLoading.value = false
  }
}

onMounted(load)

function retry() {
  load()
}

// PartyController::create() shows events.cantcreate instead of the form
// for a Restarter (always), or a Host who hosts zero groups
// (groupsInChargeOf() empty). Administrators/NetworkCoordinators always see
// the form (NC's true group count isn't knowable here - see above, a safe
// false-negative would instead show cantcreate to a legitimate NC with no
// directly-hosted group, which is the same trade-off api-gaps.md already
// documents for canedit/canApprove elsewhere).
const cantCreate = computed(() => {
  if (groupsLoading.value || isAdmin.value) return false
  if (hasRole('Restarter')) return true
  return hasRole('Host') && groupOptions.value.length === 0
})

// Legacy (EventAddEditPage.vue's eventCreated()) stays on the same
// SPA-mounted component and swaps in the edit view in place, showing an
// inline "created" banner via window.history.replaceState. Nuxt has real
// client-side routing, so this instead navigates to /party/edit/{id} -
// same choice + rationale as GroupCreatePage.vue's onCreated (design.md
// §6.2 B6): a real navigation is simpler than reproducing the SPA swap, and
// lands the user on the one page (edit) that shows the event's current
// approval state.
async function onCreated(id) {
  await navigateTo(`/party/edit/${id}`)
}
</script>

<template>
  <div class="container py-4" data-testid="party-create-page">
    <h1>{{ t('events.add_new_event') }}</h1>

    <div v-if="groupsLoading" data-testid="party-create-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="groupsError" :model-value="true" variant="danger" data-testid="party-create-groups-error">
      <p>{{ t('client.events.groups_load_error') }}</p>
      <BButton variant="danger" data-testid="party-create-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <div v-else-if="cantCreate" data-testid="party-cantcreate">
      <h2>{{ t('cantcreate.heading') }}</h2>
      <p>{{ t('cantcreate.intro') }}</p>
      <p>
        <strong>{{ t('cantcreate.not_host_q') }}</strong>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('cantcreate.not_host_body')" />
      </p>
      <p>
        <strong>{{ t('cantcreate.want_to_add_group_q') }}</strong>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('cantcreate.want_to_add_group_body')" />
      </p>
      <p>
        <strong>{{ t('cantcreate.start_new_group_q') }}</strong>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('cantcreate.start_new_group_body')" />
      </p>
    </div>

    <EventForm
      v-else
      :groups="groupOptions"
      :is-admin="isAdmin"
      :initial-group-id="initialGroupId"
      @created="onCreated"
    />
  </div>
</template>
