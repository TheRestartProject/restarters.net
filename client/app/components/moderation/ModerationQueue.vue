<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModerationStore } from '~/stores/moderation.js'

// Admin / NetworkCoordinator moderation queue, ported from the legacy
// EventsRequiringModeration / GroupsRequiringModeration components (a navigational
// listing of items awaiting approval, each linking to its page where the approve
// controls live). The PARENT page is responsible for role-gating (only render
// this for Administrators / NetworkCoordinators) - the API enforces the same.
const props = defineProps({
  // 'events' | 'groups'
  type: {
    type: String,
    required: true,
    validator: (v) => ['events', 'groups'].includes(v),
  },
})

const { t } = useI18n()
const moderationStore = useModerationStore()

const section = computed(() => moderationStore[props.type])
const title = computed(() => t(`client.moderation.${props.type}_title`))

function itemLink(item) {
  return props.type === 'events' ? `/party/view/${item.id}` : `/group/view/${item.id}`
}

onMounted(() => {
  if (props.type === 'events') {
    moderationStore.fetchEvents().catch(() => {})
  } else {
    moderationStore.fetchGroups().catch(() => {})
  }
})
</script>

<template>
  <!-- Only surfaces when there is something to moderate, matching the legacy
       behaviour (the panel didn't render on an empty queue). -->
  <section
    v-if="section.data.length"
    class="panel panel__orange mb-4"
    :data-testid="`moderation-queue-${type}`"
  >
    <h3 class="mb-3">{{ title }}</h3>
    <ul class="list-unstyled mb-0">
      <li v-for="item in section.data" :key="item.id" class="mb-2">
        <NuxtLink :to="itemLink(item)" :data-testid="`moderation-queue-${type}-item-${item.id}`">
          {{ item.name || item.title }}
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
