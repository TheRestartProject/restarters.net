<template>
  <CollapsibleSection class="lineheight" collapsed hide-title>
    <template slot="title">
      {{ translatedDescription }}
    </template>
    <template slot="content">
      <read-more :html="free_text" class="mt-2" :max-chars="440" :more-str="translatedReadMore" :less-str="translatedReadLess" />
    </template>
  </CollapsibleSection>
</template>
<script>
import map from '../mixins/map'
import group from '../mixins/group'
import ExternalLink from './ExternalLink'
import CollapsibleSection from './CollapsibleSection'
import ReadMore from './ReadMore'

export default {
  components: {ReadMore, CollapsibleSection, ExternalLink},
  mixins: [ map, group ],
  computed: {
    translatedDescription() {
      return this.$lang.get('groups.about')
    },
    translatedReadMore() {
      return this.$lang.get('groups.read_more')
    },
    translatedReadLess() {
      return this.$lang.get('groups.read_less')
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