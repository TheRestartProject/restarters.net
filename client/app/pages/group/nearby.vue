<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupCard from '~/components/groups/GroupCard.vue'

// /group/nearby - resources/views/group/index.blade.php (tab="nearby") +
// resources/js/components/GroupsPage.vue's "Other groups nearby" tab is the
// functional spec.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()
const dashboardStore = useDashboardStore()

// Whether the user has a town/city set. Legacy's "Other groups nearby" tab
// shows a distinct "set a location in your profile" prompt when they don't,
// vs the plain "nothing nearby" copy when they do. GET /api/v2/groups/nearby
// carries no such flag, but GET /api/v2/dashboard does (`has_location`, the
// same source the dashboard + /party pages use). Default true so we never
// flash the location prompt while the dashboard is still loading.
const hasLocation = computed(() => dashboardStore.data?.has_location ?? true)
const groups = computed(() => groupsStore.nearby.data ?? [])

function retry() {
  groupsStore.fetchNearby()
}

onMounted(() => {
  groupsStore.fetchNearby()
  dashboardStore.fetch().catch(() => {})
})
</script>

<template>
  <div class="container py-4" data-testid="group-nearby-page">
    <h1 class="d-flex justify-content-between align-items-start">
      {{ t('groups.groups') }}
      <NuxtLink to="/group/create" class="btn btn-primary" data-testid="group-create-link">
        {{ t('groups.create_groups') }}
      </NuxtLink>
    </h1>

    <GroupsTabsNav active="nearby" />

    <div v-if="groupsStore.nearby.loading" data-testid="group-nearby-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="groupsStore.nearby.error"
      :model-value="true"
      variant="danger"
      data-testid="group-nearby-error"
    >
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-nearby-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-if="!hasLocation" data-testid="group-nearby-no-location" v-html="t('groups.no_groups_nearest_no_location')" />
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-else-if="!groups.length" data-testid="group-nearby-empty" v-html="t('groups.no_groups_nearest_with_location')" />
      <div v-else data-testid="group-nearby-list">
        <GroupCard
          v-for="group in groups"
          :key="group.id"
          :group="group"
          :is-member="groupsStore.isMember(group.id)"
        />
      </div>
    </template>
  </div>
</template>
