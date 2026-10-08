<script setup>
import { useI18n } from 'vue-i18n'

// Legacy GroupActions.vue: every group-header action lives in one "GROUP
// ACTIONS" dropdown instead of a row of loose buttons - Edit / Add event /
// Invite volunteers / Share group stats / Export group data / Join-or-Leave
// / Archive, each gated the same way as develop (two branches: editors get
// the full menu, everyone else gets a much smaller one). Rendered once, in
// the header's right-hand column (see pages/group/view/[id].vue) - develop
// also duplicates it above the mobile h1, which isn't reproduced here since
// this single instance is already visible at every breakpoint.
//
// Join/leave call straight into groupsStore (via emits handled by the page)
// rather than develop's legacy `/group/join/{id}` full-page link for
// non-editors - there's no server-rendered join confirmation page in the
// SPA, and the API-backed join/leave flow already used elsewhere (
// GroupJoinButton.vue) covers both roles consistently.
defineProps({
  groupId: {
    type: Number,
    required: true,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  isMember: {
    type: Boolean,
    default: false,
  },
  canPerformArchive: {
    type: Boolean,
    default: false,
  },
  archived: {
    type: Boolean,
    default: false,
  },
  // Administrator-only permanent delete, distinct from archive. GroupActions.vue
  // shows the item to anyone who can see it and greys it out when the group
  // can't actually be deleted (it has an event with a device), rather than
  // hiding it - so an admin learns the option exists and why it's unavailable.
  canSeeDelete: {
    type: Boolean,
    default: false,
  },
  canPerformDelete: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['invite', 'share-stats', 'join', 'leave', 'archive', 'delete'])

const { t } = useI18n()
</script>

<template>
  <!-- GroupActions.vue:4 - no `no-caret`, `class="deepnowrap"`. NB unlike
       EventActions.vue this label is NOT uppercased in develop. -->
  <BDropdown
    variant="primary"
    :text="t('groups.group_actions')"
    class="deepnowrap"
    data-testid="group-actions-dropdown"
  >
    <template v-if="canedit">
      <BDropdownItem :to="`/group/edit/${groupId}`" data-testid="group-actions-edit">
        {{ t('groups.edit_group') }}
      </BDropdownItem>
      <BDropdownItem :to="`/party/create/${groupId}`" data-testid="group-actions-add-event">
        {{ t('events.add_event') }}
      </BDropdownItem>
      <BDropdownItem data-testid="group-actions-invite" @click="emit('invite')">
        {{ t('groups.invite_volunteers') }}
      </BDropdownItem>
      <BDropdownItem data-testid="group-actions-share-stats" @click="emit('share-stats')">
        {{ t('groups.share_group_stats') }}
      </BDropdownItem>
      <BDropdownItem :href="`/export/devices/group/${groupId}`" data-testid="group-actions-export">
        {{ t('groups.export_group_data') }}
      </BDropdownItem>
      <BDropdownItem v-if="isMember" data-testid="group-actions-leave" @click="emit('leave')">
        {{ t('groups.leave_group_button') }}
      </BDropdownItem>
      <BDropdownItem v-else data-testid="group-actions-join" @click="emit('join')">
        {{ t('groups.join_group_button') }}
      </BDropdownItem>
      <BDropdownItem
        v-if="canPerformArchive"
        :disabled="archived"
        data-testid="group-actions-archive"
        @click="emit('archive')"
      >
        {{ t('groups.archive_group') }}
      </BDropdownItem>
      <BDropdownItem
        v-if="canSeeDelete"
        :disabled="!canPerformDelete"
        data-testid="group-actions-delete"
        @click="canPerformDelete && emit('delete')"
      >
        {{ t('groups.delete_group') }}
      </BDropdownItem>
    </template>
    <template v-else>
      <BDropdownItem v-if="isMember" data-testid="group-actions-invite" @click="emit('invite')">
        {{ t('groups.invite_volunteers') }}
      </BDropdownItem>
      <BDropdownItem v-else data-testid="group-actions-join" @click="emit('join')">
        {{ t('groups.join_group_button') }}
      </BDropdownItem>
      <BDropdownItem data-testid="group-actions-share-stats" @click="emit('share-stats')">
        {{ t('groups.share_group_stats') }}
      </BDropdownItem>
      <BDropdownItem v-if="isMember" data-testid="group-actions-leave" @click="emit('leave')">
        {{ t('groups.leave_group_button') }}
      </BDropdownItem>
    </template>
  </BDropdown>
</template>
