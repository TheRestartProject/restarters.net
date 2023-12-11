<template>
  <b-modal
      id="confirmmodal"
      v-model="showModal"
      :title="translatedShareTitle"
      no-stacking
      @shown="shown"
      size="md"
  >
    <template slot="default">
      For debugging, you can change the value.
      <b-form-input v-model="currentCount" class="mb-4" />
      <p>
        This image is {{ width }}x{{ height }} pixels.
      </p>
      <canvas ref="canvas" :width="width" :height="height" class="canvas" :key="'canvas-' + bump" />
    </template>
    <template slot="modal-footer" slot-scope="{ ok, cancel }">
      <!-- eslint-disable-next-line -->
      <b-button variant="white" @click="cancel" v-html="translatedCancel" />
      <!-- eslint-disable-next-line -->
      <b-button variant="primary" @click="confirm" v-html="translatedDownload" />
    </template>
  </b-modal>
</template>
<script>

const MARG = 10
const RADIUS = 8

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
      width: 1080,
      height: 1080,
      backgroundColor: '#0394a6',
      currentCount: null,
      bump: 1,
    }
  },
  computed: {
    translatedCancel() {
      return this.$lang.get('partials.cancel')
    },
    translatedDownload() {
      return this.$lang.get('partials.download')
    },
    translatedShareTitle() {
      return this.$lang.get('partials.share_modal_title')
    },
  },
  watch: {
    count: function() {
      this.currentCount = this.count
    },
    currentCount: function() {
      this.paint()
    }
  },
  methods: {
    show() {
      this.showModal = true
      this.currentCount = this.count
    },
    shown() {
      this.paint()
    },
    hide() {
      this.showModal = false
    },
    confirm() {
      this.$emit('confirm')
      this.hide()
    },
    async paint() {
      try {
        this.bump++
        await this.$nextTick()

        this.canvas = this.$refs.canvas
        this.ctx = this.canvas.getContext('2d')
        const canvas = this.canvas
        const ctx = this.ctx
        ctx.font = "bold 55px Asap, sans-serif"

        // Set background color
        ctx.fillStyle = this.backgroundColor;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        let x = 0
        let y = 100

        // Get length of the whole line including the kg value.
        let str = this.currentCount + ' kg'
        let text = this.$lang.get('partials.share_modal_intro1') + ' ' + str + ' ' + this.$lang.get('partials.share_modal_intro2')

        // Use the line height of this as our standard for moving down the image.
        const lineHeight = ctx.measureText(text).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2

        x = this.fillCentredText(this.$lang.get('partials.share_modal_intro1') + ' ', x, y, text)
        x = this.fillWhiteBlackBox(str, x, y)
        x = this.fillText(' ' + this.$lang.get('partials.share_modal_intro2'), x, y)

        // Next line
        y += lineHeight
        text = this.$lang.get('partials.share_modal_intro3')
        x = this.fillCentredText(' ' + this.$lang.get('partials.share_modal_intro3'), x, y)

        // Space for graphical tilde.
        y += lineHeight
        y += lineHeight

        // That's like text
        y += lineHeight
        str = this.seedlings(this.currentCount)
        text = this.$lang.get('partials.share_modal_like1') + ' '
        x = this.fillCentredText(text, x, y, text + str)
        x = this.fillWhiteBlackBox(str, x, y)

        y += lineHeight
        x = this.fillCentredText(this.$lang.get('partials.share_modal_like2'), x, y)
      } catch (e) {
        console.log('Paint error', e)
      }
    },
    seedlings(val) {
      // 1 tree is 60 kg.
      return Math.round(val / 60)
    },
    fillText(str, x, y, colour) {
      console.log('Fill', str, x, y, colour)
      const canvas = this.canvas
      const ctx = this.ctx

      // Write the text.
      ctx.fillStyle = colour || 'black'
      ctx.strokeStyle = colour || 'black'
      ctx.fillText(str, x, y)

      // Return where we're up to.
      x += ctx.measureText(str).width
      console.log('Returning x', x)
      return x
    },
    fillCentredText(text, x, y, wholeLine) {
      console.log('Fill centred', text, x, y, wholeLine)
      const length = this.ctx.measureText(wholeLine ? wholeLine : text).width
      x = (this.canvas.width - length) / 2;
      x = this.fillText(text, x, y)
      return x
    },
    fillWhiteBlackBox(str, x, y) {
      console.log('Fill white on black', str, x, y)
      const ctx = this.ctx
      // ctx.roundRect(x, y - ctx.measureText(str).emHeightAscent - MARG, ctx.measureText(str).width + MARG * 2, ctx.measureText(str).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2, RADIUS)
      // ctx.fill()
      ctx.fillStyle = 'black';
      ctx.fillRect(x, y - ctx.measureText(str).emHeightAscent - MARG, ctx.measureText(str).width + MARG * 2, ctx.measureText(str).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2)

      x += MARG
      x = this.fillText(str, x, y, 'white')
      x += MARG

      return x
    }
  }
}
</script>
<style scoped lang="scss">
.canvas {
  max-width: 100%;
}
</style>