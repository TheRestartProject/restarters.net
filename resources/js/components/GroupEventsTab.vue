<template>
  <b-tab :active="active" title-item-class="w-50" class="pt-2" lazy>
    <template slot="title">
      <div class="d-flex justify-content-between">
        <div>
          <b>{{ __(this.title) }}</b> ({{ events.length }})
        </div>
      </div>
    </template>
    <!--        eslint-disable-next-line-->
    <p v-if="!events.length" v-html="translatedNoneMessage" />
    <GroupEventScrollTable v-else :limit="limit" :events="events" :canedit="canedit" :add-group-name="addGroupName" :past="past" :filters="filters" />
  </b-tab>
</template>
<script>
import GroupEventScrollTable from './GroupEventScrollTable.vue'

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
    },
    past: {
      type: Boolean,
      required: false,
      default: false
    },
    filters: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    translatedNoneMessage() {
      return this.__(this.noneMessage)
    },
  }
}
</script>