<template>
  <b-modal
      id="groupInfoModal"
      v-model="showModal"
      no-stacking
  >
    <template slot="modal-title">
      <div class="d-flex w-100">
        <b-img @error="brokenGroupImage" :src="groupImage" class="groupImage mr-4" />
        <div>
          <div>{{ group.name }}</div>
          <div class="small text-muted" v-if="group.location">{{ group.location.location }}</div>
        </div>
      </div>
    </template>
    <template slot="default">
      <div class="d-flex flex-wrap">
        {{  __('groups.next_event') }}:&nbsp;
        <span v-if="nextEventDate">
          {{ nextEventDate }} {{ group.next_event.title}}
        </span>
        <span v-else>
           {{ __('groups.upcoming_none_planned') }}
        </span>
      </div>
    </template>
    <template slot="modal-footer" slot-scope="{ cancel }">
      <div class="w-100 d-flex justify-content-between">
        <b-button variant="primary" :to="'/group/view/' + group.id">
          {{ __('groups.goto_group') }}
        </b-button>
        <b-button variant="secondary" @click="cancel">
          {{ __('partials.close') }}
        </b-button>
      </div>
    </template>
  </b-modal>
</template>
<script>
import map from '../mixins/map'
import {DEFAULT_PROFILE} from "../constants";
import moment from 'moment'

export default {
  components: {},
  mixins: [map],
  props: {
    id: {
      type: Number,
      required: true,
    },
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.id)
    },
    nextEventDate() {
      return this.group && this.group.next_event ? new moment(this.group.next_event.start).format('ddd Do MMM YYYY') : null
    },
    groupImage() {
      return this.group && this.group.image ? ('/uploads/mid_' + this.group.group_image) : DEFAULT_PROFILE
    },
  },
  data: function() {
    return {
      showModal: true
    }
  },
  watch: {
    showModal(newVal) {
      if (!newVal) {
        this.$emit('close')
      }
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    brokenGroupImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
  }
}
</script>
<style scoped lang="scss">
.groupImage {
  width: 67px;
}
</style>
