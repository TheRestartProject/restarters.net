<template>
  <div>
    <div class="d-flex justify-content-around">
      <div v-if="event.requiresModeration" class="cell-warning d-flex justify-content-around p-2">
        <span v-if="event.canModerate">
          <a :href="'/party/edit/' + idevents">{{ __('partials.event_requires_moderation') }}</a>
        </span>
        <span v-else>
        {{ __('partials.event_requires_moderation_by_an_admin') }}
      </span>
      </div>
      <div v-else-if="upcoming" class="hidecell">
        <div v-if="attending" class="text-black font-weight-bold d-flex justify-content-around">
        <span>
          {{ __('events.youre_going') }}
        </span>
        </div>
        <!-- "all" or "nearby" events are for ones where we're not a member, so show a join button. -->
        <b-btn variant="primary" :href="'/group/join/' + event.group.idgroups" v-else-if="event.all || event.nearby">
          {{ __('groups.join_group_button') }}
        </b-btn>
        <!-- We can't RSVP if the event is starting soon. -->
        <b-btn variant="primary" :href="event.invitation" :disabled="startingSoon" v-else-if="event.invitation">
          {{ __('events.RSVP') }}
        </b-btn>
        <b-btn variant="primary" :href="'/party/join/' + idevents" :disabled="startingSoon" v-else>
          {{ __('events.RSVP') }}
        </b-btn>
      </div>
    </div>
  </div>
</template>
<script>
import event from '../mixins/event'

export default {
  mixins: [event],
  props: {
    idevents: {
      type: Number,
      required: true
    },
  },
}
</script>
<style scoped lang="scss">
</style>