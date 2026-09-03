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
const showConfirm = ref(false)

function onClick() {
  // Leaving/unfollowing needs confirmation first (legacy: GroupsTable.vue's
  // ConfirmModal gate on the Leave/Unfollow action, groups.leave_group_confirm);
  // joining is immediate, same as before.
  if (props.isMember) {
    showConfirm.value = true
    return
  }

  doJoin()
}

async function doJoin() {
  pending.value = true

  try {
    await groupsStore.join(props.groupId)
  } catch {
    // Store already reverted the optimistic state and pushed a toast.
  } finally {
    pending.value = false
  }
}

async function confirmLeave() {
  showConfirm.value = false
  pending.value = true

  try {
    await groupsStore.leave(props.groupId)
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
    <span class="d-block d-md-none">
      {{ isMember ? t('groups.leave_group_button_mobile') : t('groups.join_group_button_mobile') }}
    </span>
    <span class="d-none d-md-block">
      {{ isMember ? t('groups.leave_group_button') : t('groups.join_group_button') }}
    </span>
  </BButton>

  <BModal
    v-if="isMember"
    :model-value="showConfirm"
    :title="t('partials.are_you_sure')"
    no-footer
    :data-testid="`group-leave-confirm-${groupId}`"
    @hide="showConfirm = false"
  >
    <p>{{ t('groups.leave_group_confirm') }}</p>
    <div class="d-flex justify-content-end gap-2">
      <BButton variant="outline-secondary" @click="showConfirm = false">
        {{ t('partials.cancel') }}
      </BButton>
      <BButton
        variant="primary"
        :disabled="pending"
        :data-testid="`group-leave-confirm-button-${groupId}`"
        @click="confirmLeave"
      >
        {{ t('groups.leave_group_button') }}
      </BButton>
    </div>
  </BModal>
</template>
