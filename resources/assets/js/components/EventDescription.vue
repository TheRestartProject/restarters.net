<template>
  <CollapsibleSection class="lineheight" collapsed hide-title>
    <template slot="title">
      {{ translatedDescription }}
    </template>
    <template slot="content">
      <read-more :text="free_text" class="mt-2 readmore small" :max-chars="440" :more-str="translatedReadMore" :less-str="translatedReadLess" />
    </template>
  </CollapsibleSection>
</template>
<script>
import { DATE_FORMAT } from '../constants'
import moment from 'moment'
import map from '../mixins/map'
import ExternalLink from './ExternalLink'
import CollapsibleSection from './CollapsibleSection'
import ReadMore from './ReadMore'
const htmlToText = require('html-to-text');

export default {
  components: {ReadMore, CollapsibleSection, ExternalLink},
  mixins: [ map ],
  props: {
    eventId: {
      type: Number,
      required: true
    },
    event: {
      type: Object,
      required: true
    }
  },
  computed: {
    free_text() {
      // Strip HTML
      let ret = htmlToText.fromString(this.event.free_text);

      // Remove duplicate blank lines.
      ret = ret.replace(/(\r\n|\r|\n){2,}/g, '$1\n');

      return ret
    },
    translatedDescription() {
      return this.$lang.get('events.event_description')
    },
    translatedReadMore() {
      return this.$lang.get('events.read_more')
    },
    translatedReadLess() {
      return this.$lang.get('events.read_less')
    },
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

.icon {
  width: 30px;
}
</style>