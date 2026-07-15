<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'

// /group (mine) - resources/views/group/index.blade.php (tab="mine") +
// resources/js/components/GroupsPage.vue's "Your Groups" tab is the
// functional spec.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()

// GET /api/v2/dashboard's your_groups (max 5) is the only source for "groups
// the current user belongs to" today - see stores/groups.js's doc comment
// and docs/nuxt-migration/api-gaps.md. It carries no location/hosts/
// restarters/next_event, so those columns are switched off here.
const rows = computed(() =>
  groupsStore.mine.data.map((group) => ({
    id: group.id,
    name: group.name,
    archivedAt: group.archived ? true : null,
    role: group.role,
    isMember: true,
  }))
)

function retry() {
  groupsStore.fetchMine()
}

onMounted(() => {
  groupsStore.fetchMine()
})
</script>

<template>
  <div class="container py-4" data-testid="group-mine-page">
    <h1 class="d-flex justify-content-between align-items-start">
      {{ t('groups.groups') }}
      <NuxtLink to="/group/create" class="btn btn-primary" data-testid="group-create-link">
        {{ t('groups.create_groups') }}
      </NuxtLink>
    </h1>

    <GroupsTabsNav active="mine" />

    <div v-if="groupsStore.mine.loading" data-testid="group-mine-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="groupsStore.mine.error" :model-value="true" variant="danger" data-testid="group-mine-error">
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-mine-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-if="!rows.length" data-testid="group-mine-empty" v-html="t('groups.no_groups_mine')" />
      <div v-else data-testid="group-mine-table">
        <GroupsTable
          :groups="rows"
          :show-role="true"
          :optional-columns="{ location: false, hosts: false, restarters: false, next_event: false }"
        />
      </div>
    </template>
  </div>
</template>
