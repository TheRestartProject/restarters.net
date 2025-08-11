<template>
  <CollapsibleSection class="no-explict-width" collapsed>
    <template slot="title">
      {{ __('groups.about') }}
    </template>
    <template slot="content">
      <GroupArchivedBadge :idgroups="idgroups" v-if="idgroups" />
      <div class="d-flex flex-column justify-content-between">
        <div>
          <p v-if="!group.free_text" class="text-muted">
            {{ __('groups.about_none') }}
          </p>
          <read-more v-else :html="group.free_text" class="mt-2" :max-chars="440" :more-str="__('groups.read_more')" :less-str="__('groups.read_less')" />
        </div>
        <p v-if="group.phone" class="font-weight-bold">
          {{ __('groups.field_phone') }}:
          <a :href="'tel:' +  group.phone">
            {{ group.phone }}
          </a>
        </p>
        <div class="d-flex pt-1 pb-1" v-if="group.email">
          <div class="mr-2">
            <b-img-lazy :src="imageUrl('/images/mail_ico.svg')" class="icon" />
          </div>
          <div>
            <a :href="'mailto:' + group.email">{{ group.email }}</a>
          </div>
        </div>
        <div class="d-flex pt-1 pb-1" v-if="discourseGroup">
          <div class="mr-2">
            <b-img-lazy :src="imageUrl('/icons/talk_ico.svg')" class="icon" />
          </div>
          <div>
            <a :href="discourseGroup">{{ __('groups.talk_group') }}</a>
          </div>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import map from '../mixins/map'
import group from '../mixins/group'
import images from '../mixins/images'
import ExternalLink from './ExternalLink.vue'
import CollapsibleSection from './CollapsibleSection.vue'
import ReadMore from './ReadMore.vue'
import GroupArchivedBadge from "./GroupArchivedBadge.vue";

export default {
  components: {GroupArchivedBadge, ReadMore, CollapsibleSection, ExternalLink},
  mixins: [ map, group, images ],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    discourseGroup: {
      type: String,
      required: false,
      default: null
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

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