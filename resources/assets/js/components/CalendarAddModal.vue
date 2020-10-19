<template>
  <b-modal
      id="calendar"
      v-model="showModal"
      no-stacking
      no-body
      size="lg"
  >
    <template slot="modal-title">
      <slot name="title" />
    </template>
    <slot name="description" />
    <b-input-group class="mt-2">
      <b-input readonly size="lg" :value="copyUrl" ref="container" />
      <b-input-group-append>
        <b-button variant="white" class="butt" @click="copyIt">
          <b-img src="/images/copy.svg" />
        </b-button>
      </b-input-group-append>
    </b-input-group>
    <b-alert v-if="failed" show variant="danger" class="mt-2 mb-2">
      <p>
        {{ translatedSomethingWrong }}
      </p>
    </b-alert>
    <div class="d-flex justify-content-between flex-wrap mt-4 mb-4">
      <b-btn variant="link" href="https://talk.restarters.net/t/fixometer-how-to-add-repair-events-to-your-calendar-application/1770">
        {{ translatedFindOutMore}}
      </b-btn>
      <b-btn variant="link" :href="editUrl">
        {{ translatedSeeAll }}
      </b-btn>
    </div>
    <template slot="modal-footer" slot-scope="{ cancel }">
      <b-button variant="primary" @click="cancel">
        {{ translatedClose }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'

Vue.use(VueClipboard)

export default {
  props: {
    copyUrl: {
      type: String,
      required: true
    },
    editUrl: {
      type: String,
      required: true
    },
  },
  data: function() {
    return {
      showModal: false,
      failed: false
    }
  },
  computed: {
    translatedSomethingWrong() {
      return this.$lang.get('partials.something_wrong')
    },
    translatedClose() {
      return this.$lang.get('partials.close')
    },
    translatedFindOutMore() {
      return this.$lang.get('calendars.find_out_more')
    },
    translatedSeeAll() {
      return this.$lang.get('calendars.see_all_calendars')
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    async copyIt() {
      try {
        this.$refs.container.select()
        await this.$copyText(this.$refs.container)
        this.hide()
      } catch (e) {
        this.failed = true
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.butt {
  border-radius: 0;
  border: 1px solid black;
  height: 45px;
}
</style>