<template>
  <CollapsibleSection class="lineheight no-explict-width" collapsed hide-title>
    <template slot="title">
      {{ translatedDescription }}
    </template>
    <template slot="content">
      <read-more :html="event.free_text" class="mt-2 readmore small" :max-chars="440" :more-str="translatedReadMore" :less-str="translatedReadLess" />
    </template>
  </CollapsibleSection>
</template>
<script>
import map from '../mixins/map'
import event from '../mixins/event'
import ExternalLink from './ExternalLink'
import CollapsibleSection from './CollapsibleSection'
import ReadMore from './ReadMore'

export default {
  components: {ReadMore, CollapsibleSection, ExternalLink},
  mixins: [ map, event ],
  computed: {
    translatedDescription() {
      return this.$lang.get('events.event_description')
    },
    translatedReadMore() {
      return this.$lang.get('events.read_more')
    },
    translatedReadLess() {
      return this.$lang.get('events.read_less')
    }
  }
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