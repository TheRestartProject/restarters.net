<script setup>
import { useI18n } from 'vue-i18n'

// Gap 2: develop's EventActions.vue consolidates every event-header action
// into a single "EVENT ACTIONS" b-dropdown, rather than a row of individual
// buttons - same anti-pattern already fixed for the group-view page via
// components/groups/GroupActions.vue, which this mirrors. Two branches
// (canedit / not), matching EventActions.vue's item set and v-if conditions
// as closely as this SPA's existing (documented, stricter) permission model
// allows - see pages/party/view/[id].vue's canedit/candelete/invite-gating
// comments, which this component doesn't relax.
//
// RSVP has no separate standalone button here (develop's only RSVP entry
// point when not attending is this dropdown's `/party/join/{id}` item) -
// EXCEPT for a logged-out viewer, who gets a plain "log in to RSVP" link
// outside the dropdown (pages/party/view/[id].vue), since none of this
// dropdown's other items make sense to an anonymous visitor.
defineProps({
  eventId: {
    type: Number,
    required: true,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  candelete: {
    type: Boolean,
    default: false,
  },
  // EventActions.vue's Delete item has 3 states, not 2: enabled (candelete),
  // visible-but-disabled (isAdmin without candelete - an admin can always
  // SEE the item, just can't act while the event still has devices), or
  // omitted entirely (a canedit host who isn't candelete and isn't admin
  // never sees it at all) - so this needs its own prop, distinct from the
  // page's `canedit`/`candelete` gates it sits alongside.
  isAdmin: {
    type: Boolean,
    default: false,
  },
  isAttending: {
    type: Boolean,
    default: false,
  },
  upcoming: {
    type: Boolean,
    default: false,
  },
  approved: {
    type: Boolean,
    default: true,
  },
  finished: {
    type: Boolean,
    default: false,
  },
  inGroup: {
    type: Boolean,
    default: false,
  },
  hasGroup: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['rsvp', 'invite', 'request-review', 'share-stats', 'follow-group', 'delete'])

const { t } = useI18n()
</script>

<template>
  <!-- EventActions.vue:3 - `:text="__('events.event_actions').toUpperCase()"`,
       no `no-caret`, `class="deepnowrap"`. The caret is not decoration we can
       drop: _buttons.scss carries develop's
       `.dropdown-toggle[aria-expanded='true']::after { transform: rotate(180deg) }`
       open/close animation, which `no-caret` silently made dead code here. -->
  <BDropdown
    variant="primary"
    :text="t('events.event_actions').toUpperCase()"
    class="deepnowrap"
    data-testid="event-actions-dropdown"
  >
    <template v-if="canedit">
      <BDropdownItem :to="`/party/edit/${eventId}`" data-testid="event-actions-edit">
        {{ t('events.edit_event') }}
      </BDropdownItem>
      <BDropdownItem :to="`/party/duplicate/${eventId}`" data-testid="event-actions-duplicate">
        {{ t('events.duplicate_event') }}
      </BDropdownItem>
      <!-- EventActions.vue: candelete -> enabled; isAdmin-without-candelete
           -> visible but disabled; neither -> omitted entirely (a host who
           isn't admin and can't delete never sees the item at all). -->
      <BDropdownItem v-if="candelete" data-testid="event-actions-delete" @click="emit('delete')">
        {{ t('events.delete_event') }}
      </BDropdownItem>
      <BDropdownItem v-else-if="isAdmin" :disabled="true" data-testid="event-actions-delete">
        {{ t('events.delete_event') }}
      </BDropdownItem>
      <template v-if="finished">
        <BDropdownItem data-testid="event-actions-request-review" @click="emit('request-review')">
          {{ t('events.request_review') }}
        </BDropdownItem>
        <BDropdownItem data-testid="event-actions-share-stats" @click="emit('share-stats')">
          {{ t('events.share_event_stats') }}
        </BDropdownItem>
        <BDropdownItem :href="`/export/devices/event/${eventId}`" data-testid="event-actions-export">
          {{ t('devices.export_event_data') }}
        </BDropdownItem>
      </template>
      <template v-else>
        <!-- Invite requires the viewer to already be attending themselves
             (EventActions.vue's `isAttending && upcoming` gate) - shown
             disabled with a tooltip while attending-but-not-yet-approved,
             omitted entirely otherwise. RSVP stays a separate, independent
             condition (not view.blade.php's exact if/else-if/else chain,
             which would also resurface RSVP for an already-attending viewer
             once the event is in progress - not something this audit
             flagged, and worse UX than just keying RSVP off `!isAttending`). -->
        <BDropdownItem v-if="isAttending && upcoming && approved" data-testid="event-actions-invite" @click="emit('invite')">
          {{ t('events.invite_volunteers') }}
        </BDropdownItem>
        <BDropdownItem
          v-else-if="isAttending && upcoming"
          id="event-actions-invite-disabled"
          :disabled="true"
          data-testid="event-actions-invite"
        >
          {{ t('events.invite_volunteers') }}
        </BDropdownItem>
        <BPopover
          v-if="isAttending && upcoming && !approved"
          target="event-actions-invite-disabled"
          triggers="hover focus"
          data-testid="event-actions-invite-tooltip"
        >
          {{ t('events.invite_when_approved') }}
        </BPopover>
        <BDropdownItem v-if="!isAttending" data-testid="event-actions-rsvp" @click="emit('rsvp')">
          {{ t('events.RSVP') }}
        </BDropdownItem>
        <BDropdownItem v-if="hasGroup && !inGroup" data-testid="event-actions-follow-group" @click="emit('follow-group')">
          {{ t('groups.join_group_button') }}
        </BDropdownItem>
      </template>
    </template>
    <template v-else>
      <BDropdownItem v-if="finished" data-testid="event-actions-share-stats" @click="emit('share-stats')">
        {{ t('events.share_event_stats') }}
      </BDropdownItem>
      <template v-else>
        <BDropdownItem v-if="hasGroup && !inGroup" data-testid="event-actions-follow-group" @click="emit('follow-group')">
          {{ t('groups.join_group_button') }}
        </BDropdownItem>
        <!-- EventActions.vue's non-canedit Invite has no approved/disabled
             state at all - just isAttending && upcoming. -->
        <BDropdownItem v-if="isAttending && upcoming" data-testid="event-actions-invite" @click="emit('invite')">
          {{ t('events.invite_volunteers') }}
        </BDropdownItem>
        <BDropdownItem v-if="!isAttending" data-testid="event-actions-rsvp" @click="emit('rsvp')">
          {{ t('events.RSVP') }}
        </BDropdownItem>
      </template>
    </template>
  </BDropdown>
</template>
