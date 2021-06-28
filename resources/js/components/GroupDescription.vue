<template>
  <CollapsibleSection class="lineheight no-explict-width" collapsed>
    <template slot="title">
      {{ __('groups.about') }}
    </template>
    <template slot="content">
      <p v-if="!group.free_text" class="text-muted">
        {{ __('groups.about_none') }}
      </p>
      <read-more v-else :html="group.free_text" class="mt-2" :max-chars="440" :more-str="__('groups.read_more')" :less-str="__('groups.read_less')" />
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
  props: {
    idgroups: {
      type: Number,
      required: true
    }
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

.icon {
  width: 30px;
}

.text-muted {
  font-size: 18px !important;
}
</style>