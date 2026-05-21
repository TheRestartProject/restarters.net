<template>
  <b-modal
      id="statsmodal"
      v-model="showModal"
      :title="translatedShareTitle"
      no-stacking
      @shown="shown"
      size="md"
  >
    <template slot="default">
      <div class="w-100 d-flex justify-content-around">
        <b-button-group class="mb-4 buttons">
          <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Instagram'}" size="sm" @click="target = 'Instagram'">Instagram</b-button>
          <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Facebook'}" size="sm" @click="target = 'Facebook'">Facebook</b-button>
          <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Twitter'}" size="sm" @click="target = 'Twitter'">Twitter</b-button>
          <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'LinkedIn'}" size="sm" @click="target = 'LinkedIn'">LinkedIn</b-button>
        </b-button-group>
      </div>
      <StatsShare :count="count" :target="target" ref="stats" :painting.sync="painting" size />
    </template>
    <template slot="modal-footer" slot-scope="{ ok, cancel }">
      <!-- eslint-disable-next-line -->
      <b-button variant="white" @click="cancel" v-html="translatedClose" />
      <!-- eslint-disable-next-line -->
      <b-button variant="primary" @click="download" v-html="translatedDownload" />
    </template>
  </b-modal>
</template>
<script>
import StatsShare from "./StatsShare.vue";


export default {
  components: {StatsShare},
  props: {
    count: {
      type: Number,
      required: true,
    }
  },
  computed: {
    translatedClose() {
      return this.__('partials.close')
    },
    translatedDownload() {
      // TODO Translations.
      return this.__('partials.download')
    },
    translatedShareTitle() {
      return this.__('partials.share_modal_title')
    },
  },
  data: function() {
    return {
      showModal: false,
      target: 'Instagram',
      painting: false,
      currentCount: null,
    }
  },
  watch: {
    count: function() {
      this.currentCount = this.count
    },
  },
  methods: {
    show() {
      this.showModal = true
      this.currentCount = this.count
      const _paq = window._paq = window._paq || [];
      _paq.push(['trackEvent', 'ShareStats', 'ClickedOnButton']);
    },
    shown() {
      this.$refs.stats.paint()
    },
    hide() {
      this.showModal = false
    },
    download() {
      this.$refs.stats.download()
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

::v-deep .buttons button {
  font-size: 12px;

  color: black !important;
  background-color: white !important;

  &.active {
    color: white !important;
    background-color: black !important;
    box-shadow: 5px 5px 0 0 #222 !important;
  }

  &:not(.active) {
    z-index: 10;
  }

  @include media-breakpoint-down(sm) {
    font-size: 10px;
  }

  @include media-breakpoint-down(xs) {
    font-size: 8px;
  }
}

</style>