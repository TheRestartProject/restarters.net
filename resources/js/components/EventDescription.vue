<template>
  <CollapsibleSection class="lineheight no-explict-width" collapsed hide-title>
    <template slot="title">
      {{ __('events.event_description') }}
    </template>
    <template slot="content">
      <read-more :html="event.free_text" class="mt-2 readmore small" :max-chars="440" :more-str="__('events.read_more')" :less-str="__('events.read_less')" />
      <p v-if="!event.approved" class="small text-muted">
        {{ __('partials.event_requires_moderation_by_an_admin') }}.
      </p>
    </template>
  </CollapsibleSection>
</template>
<script>
import map from '../mixins/map'
import event from '../mixins/event'
import ExternalLink from './ExternalLink.vue'
import CollapsibleSection from './CollapsibleSection.vue'
import ReadMore from './ReadMore.vue'

export default {
  components: {ReadMore, CollapsibleSection, ExternalLink},
  mixins: [ map, event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.lineheight {
  line-height: 2;
}

.readmore {
  white-space: pre-wrap !important;
}
</style>