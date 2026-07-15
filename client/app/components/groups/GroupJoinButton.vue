<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'

// Self-join/leave toggle (api-contracts-phase-b.md B2: POST/DELETE
// /api/v2/groups/{id}/members/me). `isMember` is controlled by the parent
// (usually `groupsStore.isMember(id)`, design.md §6.2 B4 task brief) so this
// component stays a thin, easily-testable wrapper: stores/groups.js#join()/
// leave() already do the optimistic update + revert-on-error + toast, this
// just calls the right one and disables itself while the request is in
// flight.
const props = defineProps({
  groupId: {
    type: Number,
    required: true,
  },
  groupName: {
    type: String,
    default: '',
  },
  isMember: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const groupsStore = useGroupsStore()
const pending = ref(false)

async function onClick() {
  pending.value = true

  try {
    if (props.isMember) {
      await groupsStore.leave(props.groupId)
    } else {
      await groupsStore.join(props.groupId)
    }
  } catch {
    // Store already reverted the optimistic state and pushed a toast.
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <BButton
    variant="primary"
    class="text-nowrap"
    :disabled="pending"
    :data-testid="isMember ? `group-leave-${groupId}` : `group-join-${groupId}`"
    @click="onClick"
  >
    {{ isMember ? t('groups.leave_group_button') : t('groups.join_group_button') }}
  </BButton>
</template>
