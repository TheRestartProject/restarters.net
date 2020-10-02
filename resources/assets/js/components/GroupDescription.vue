<template>
  <CollapsibleSection class="lineheight" collapsed>
    <template slot="title">
      {{ translatedAbout }}
    </template>
    <template slot="content">
      <p v-if="!group.free_text" class="text-muted">
        {{ translatedNoAbout}}
      </p>
      <read-more v-else :html="group.free_text" class="mt-2" :max-chars="440" :more-str="translatedReadMore" :less-str="translatedReadLess" />
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
    translatedNoAbout() {
      return this.$lang.get('groups.about_none')
    },
    translatedAbout() {
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

.text-muted {
  font-size: 18px !important;
}
</style>