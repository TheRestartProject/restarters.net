<template>
  <b-tab :active="active" title-item-class="w-50" class="pt-2" lazy>
    <template slot="title">
      <div class="d-flex justify-content-between">
        <div>
          <b>{{ translatedTitle }}</b> ({{ events.length }})
        </div>
      </div>
    </template>
    <p v-if="!events.length">
      {{ translatedNoneMessage }}
    </p>
    <GroupEventScrollTable v-else :limit="limit" :events="events" :canedit="canedit" :add-group-name="addGroupName" />
  </b-tab>
</template>
<script>
import GroupEventScrollTable from './GroupEventScrollTable'

export default {
  components: {GroupEventScrollTable},
  props: {
    events: {
      type: Array,
      required: true
    },
    canedit: {
      type: Boolean,
      required: true
    },
    addGroupName: {
      type: Boolean,
      required: true
    },
    active: {
      type: Boolean,
      required: false,
      default: false
    },
    limit: {
      type: Number,
      required: false,
      default: null
    },
    title: {
      type: String,
      required: true
    },
    noneMessage: {
      type: String,
      required: true
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get(this.title)
    },
    translatedNoneMessage() {
      return this.$lang.get(this.noneMessage)
    },
  }
}
</script>