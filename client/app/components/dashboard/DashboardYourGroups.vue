<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// Role ints per app/Role.php (design.md contract - api-contracts-phase-b.md
// B1 `your_groups[].role`). Not exported anywhere client-side yet (the
// session's user.role_name is a string, not this int), so pinned here.
const ROLE_LABELS = {
  1: 'client.dashboard.role_root',
  2: 'client.dashboard.role_administrator',
  3: 'client.dashboard.role_host',
  4: 'client.dashboard.role_restarter',
  6: 'client.dashboard.role_network_coordinator',
}

const ROLE_VARIANTS = {
  1: 'dark',
  2: 'dark',
  3: 'primary',
  4: 'secondary',
  6: 'info',
}

const props = defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()

const sortedGroups = computed(() =>
  [...props.groups].sort((a, b) => a.name.localeCompare(b.name))
)

function roleLabel(role) {
  const key = ROLE_LABELS[role]
  return key ? t(key) : null
}

function roleVariant(role) {
  return ROLE_VARIANTS[role] || 'secondary'
}
</script>

<template>
  <div data-testid="dashboard-your-groups">
    <h2>{{ t('dashboard.your_groups_heading') }}</h2>

    <div v-if="!sortedGroups.length" data-testid="your-groups-empty">
      <p>{{ t('client.dashboard.no_your_groups') }}</p>
      <NuxtLink to="/group" data-testid="your-groups-browse-link">
        {{ t('groups.all_groups') }}
      </NuxtLink>
    </div>

    <template v-else>
      <p>{{ t('dashboard.catch_up') }}</p>

      <ul class="list-unstyled" data-testid="your-groups-list">
        <li
          v-for="group in sortedGroups"
          :key="group.id"
          class="d-flex justify-content-between align-items-center py-2 border-bottom"
          :data-testid="`your-group-${group.id}`"
        >
          <div class="d-flex align-items-center">
            <img
              :src="group.image_url || '/images/placeholder-avatar.png'"
              alt=""
              width="48"
              height="48"
              class="rounded-circle me-2"
            >
            <div>
              <NuxtLink :to="`/group/view/${group.id}`" :data-testid="`your-group-link-${group.id}`">
                {{ group.name }}
              </NuxtLink>
              <div>
                <BBadge
                  v-if="roleLabel(group.role)"
                  :variant="roleVariant(group.role)"
                  class="me-1"
                  :data-testid="`your-group-role-${group.id}`"
                >
                  {{ roleLabel(group.role) }}
                </BBadge>
                <BBadge
                  v-if="group.archived"
                  variant="secondary"
                  pill
                  :data-testid="`your-group-archived-${group.id}`"
                >
                  {{ t('groups.archived_group') }}
                </BBadge>
              </div>
            </div>
          </div>
        </li>
      </ul>

      <div class="d-flex justify-content-end">
        <NuxtLink to="/group" data-testid="your-groups-see-all">
          {{ t('dashboard.see_all_groups') }}
        </NuxtLink>
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}
</style>
