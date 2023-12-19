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
      For debugging, you can change the value.
      <b-form-input v-model="currentCount" class="mb-4" />
      <b-button-group class="mb-4 buttons">
        <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Instagram'}" size="sm" @click="target = 'Instagram'">Instagram</b-button>
        <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Facebook'}" size="sm" @click="target = 'Facebook'">Facebook</b-button>
        <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'Twitter'}" size="sm" @click="target = 'Twitter'">Twitter</b-button>
        <b-button :disabled="painting" variant="primary" :class="{ 'active': target === 'LinkedIn'}" size="sm" @click="target = 'LinkedIn'">LinkedIn</b-button>
      </b-button-group>
      <p class="text-muted small">
        This image is {{ width }}x{{ height }} pixels.
      </p>
      <div class="d-flex justify-content-around w-100">
        <canvas ref="canvas" :width="width" :height="height" class="canvas" :key="'canvas-' + bump" />
      </div>
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
const MARG = 10
const RADIUS = 8
const HECTARES = 13501

export default {
  props: {
    count: {
      type: Number,
      required: true,
    }
  },
  data: function() {
    return {
      showModal: false,
      canvas: null,
      ctx: null,
      currentCount: null,
      bump: 1,
      target: 'Instagram',
      painting: false
    }
  },
  computed: {
    width() {
      switch (this.target) {
        case 'Instagram':
          return 1080
        case 'Facebook':
          return 1200
        case 'Twitter':
          return 1600
        case 'LinkedIn':
          return 1200
      }
    },
    height() {
      switch (this.target) {
        case 'Instagram':
          return 1080
        case 'Facebook':
          return 630
        case 'Twitter':
          return 900
        case 'LinkedIn':
          return 627
      }
    },
    translatedClose() {
      return this.$lang.get('partials.close')
    },
    translatedDownload() {
      // TODO Translations.
      return this.$lang.get('partials.download')
    },
    translatedShareTitle() {
      return this.$lang.get('partials.share_modal_title')
    },
    portrait() {
      return this.target === 'Instagram'
    },
    image: function() {
      let ret = null

      if (this.currentCount <= 210) {
        ret = 'ImpactRange1'
      } else if (this.currentCount <= 3300) {
        ret = 'ImpactRange2'
      } else if (this.currentCount <= 6000) {
        ret = 'ImpactRange3'
      } else if (this.currentCount < HECTARES) {
        ret = 'ImpactRange4'
      } else if (this.currentCount <= 192000) {
        ret = 'ImpactRange5'
      } else {
        ret = 'ImpactRange6'
      }

      if (this.portrait) {
        ret += 'Portrait'
      } else {
        ret += 'Landscape'
      }

      return ret
    },
    fontSize() {
      switch (this.target) {
        case 'Instagram':
          return 55
        case 'Facebook':
          return 40
        case 'Twitter':
          return 52
        case 'LinkedIn':
          return 40
      }
    },
    initialY() {
      switch (this.target) {
        case 'Instagram':
          return 100
        case 'Facebook':
          return this.canvas.height / 5
        case 'Twitter':
          return this.canvas.height / 5
        case 'LinkedIn':
          return this.canvas.height / 5
      }
    }
  },
  watch: {
    count: function() {
      this.currentCount = this.count
    },
    currentCount: function() {
      this.paint()
    },
    target: function() {
      this.paint()
    }
  },
  methods: {
    show() {
      this.showModal = true
      this.currentCount = this.count
      const _paq = window._paq = window._paq || [];
      _paq.push(['trackEvent', 'ShareStats', 'ClickedOnButton']);
    },
    shown() {
      this.paint()
    },
    hide() {
      this.showModal = false
    },
    download() {
      try {
        const _paq = window._paq = window._paq || [];
        _paq.push(['trackEvent', 'ShareStats', 'Download', this.target]);

        let link = document.createElement('a');
        link.download = 'stats.png';
        link.href = this.canvas.toDataURL()
        link.click();
        // this.hide()
      } catch (e) {
        console.error('Failed to download', e)
      }
    },
    async paint() {
      if (!this.painting) {
        try {
          this.painting = true
          this.bump++
          await this.$nextTick()

          this.canvas = this.$refs.canvas
          this.ctx = this.canvas.getContext('2d')
          const canvas = this.canvas
          const ctx = this.ctx
          ctx.font = "bold " + this.fontSize + "px Asap, sans-serif"

          // Add background.
          this.insertImage(this.image, 0, 0, this.width, this.height, function() {
            let x = 0
            let y = this.initialY

            // Get length of the whole line including the kg value.
            let str = parseInt(this.currentCount).toLocaleString() + ' kg'
            let text = this.$lang.get('partials.share_modal_intro1') + ' ' + str + ' ' + this.$lang.get('partials.share_modal_intro2')

            // Use the line height of this as our standard for moving down the image.
            const lineHeight = ctx.measureText(text).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2

            x = this.fillCentredText(this.$lang.get('partials.share_modal_intro1') + ' ', x, y, text)
            x = this.fillWhiteBlackBox(str, x, y)
            x = this.fillText(' ' + this.$lang.get('partials.share_modal_intro2'), x, y)

            // Next line
            y += lineHeight

            text = this.$lang.get('partials.share_modal_intro3')
            x = this.fillCentredText(text, x, y)

            // Wavy divider line.
            if (this.portrait) {
              y += 30
            } else {
              y += canvas.height / 5
            }

            if (this.portrait) {
              x = (canvas.width - 292 / 2) / 2
            } else {
              x = canvas.width / 20
            }

            this.insertImage('WavyDividerLine', x, y, 292 / 2, 39 / 2)

            if (this.portrait) {
              y += 7
            } else {
              y += canvas.height / 5
            }

            // That's like text
            y += lineHeight

            if (this.currentCount < HECTARES) {
              str = this.seedlings(this.currentCount)
              text = this.$lang.get('partials.share_modal_like1') + ' '
            } else {
              str = this.hectares(this.currentCount)
              text = this.$lang.get('partials.share_modal_like3') + ' '
            }

            x = this.fillCentredText(text, x, y, text + str)
            x = this.fillWhiteBlackBox(str, x, y)

            y += lineHeight

            if (this.currentCount < HECTARES) {
              x = this.fillCentredText(this.$lang.choice('partials.share_modal_like2', str), x, y)
            } else {
              x = this.fillCentredText(this.$lang.choice('partials.share_modal_like4', str), x, y)
            }
          })
        } catch (e) {
          console.error('Paint error', e)
        }

        setTimeout(() => {
          // Canvas fettling is not entirely synchronous, so you can get weird artifacts if you switch
          // buttons too rapidly.  No easy way to fix this entirely, but this will help a lot.
          this.painting = false
        }, 2000)
      }
    },
    seedlings(val) {
      // 1 tree is 60 kg.
      return Math.round(val / 60).toLocaleString()
    },
    hectares(val) {
      // 1 hectare is 12000 kg.
      return Math.round(val / 12000).toLocaleString()
    },
    fillText(str, x, y, colour) {
      const ctx = this.ctx

      // Write the text.
      ctx.fillStyle = colour || 'black'
      ctx.strokeStyle = colour || 'black'
      ctx.fillText(str, x, y)

      // Return where we're up to.
      x += ctx.measureText(str).width
      return x
    },
    fillCentredText(text, x, y, wholeLine) {
      const length = this.ctx.measureText(wholeLine ? wholeLine : text).width

      if (this.portrait) {
        // Text should be centred on portait images.
        x = (this.canvas.width - length) / 2;
      } else {
        // Text should be left-aligned. on landscape images.
        x = this.canvas.width / 40
      }

      x = this.fillText(text, x, y)

      return x
    },
    fillWhiteBlackBox(str, x, y) {
      const ctx = this.ctx
      ctx.roundRect(x, y - ctx.measureText(str).emHeightAscent - MARG, ctx.measureText(str).width + MARG * 2, ctx.measureText(str).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2, RADIUS)
      ctx.fill()

      // Looks like we need a beginPath() to prevent future calls to roundRect working on the same rectangle and
      // therefore re-filling over what we've written.
      ctx.beginPath()

      x += MARG
      x = this.fillText(str, x, y, 'white')
      x += MARG

      return x
    },
    insertImage(name, x, y, width, height, cb) {
      const ctx = this.ctx
      const img = new Image()
      img.src = '/images/' + name + '.png'
      img.onload = () => {
        ctx.drawImage(img, x, y, width, height)

        if (cb) {
          setTimeout(cb.bind(this), 500)
        }
      }
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.canvas {
  max-width: 100%;
}

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