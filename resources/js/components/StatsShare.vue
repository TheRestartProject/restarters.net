<template>
  <div>
    <!--      <div class="bg-danger mb-4 p-2">-->
    <!--        <p class="text-white">-->
    <!--          For debugging, you can change the value.-->
    <!--        </p>-->
    <!--        <b-form-input type="number" v-model="currentCount" class="mb-4" :disabled="painting" />-->
    <!--        <div class="d-flex justify-content-between">-->
    <!--          <b-btn variant="primary" @click="paint" :disabled="painting">Update</b-btn>-->
    <!--          <b-btn variant="primary" @click="prev" :disabled="painting">Step to prev</b-btn>-->
    <!--          <b-btn variant="primary" @click="next" :disabled="painting">Step to next</b-btn>-->
    <!--        </div>-->
    <!--      </div>-->
      <p class="text-muted small" v-if="size">
        This image is {{ width }}x{{ height }} pixels.
      </p>
      <div class="d-flex justify-content-around w-100">
        <canvas ref="canvas" :width="width" :height="height" class="canvas" :key="'canvas-' + bump" />
      </div>
  </div>
</template>
<script>
const MARG = 10
const RADIUS = 8

// This is a lookup table which determines which visualisation we use (seedling, square of seedlings, hectares), and
// which variant of it.
const RANGES = [
  ['Visualisation level', 'Increment number', 'Increment type', 'CO2e per increment (kg)', 'Lower boundary (kg CO2e)', 'Exact CO2e represented', 'Upper boundary (kg CO2e)',],
  [1, 1, 'Seedling', 60, 30, 60, 89.99,],
  [1, 2, 'Seedling', 60, 90, 120, 149.99,],
  [1, 3, 'Seedling', 60, 150, 180, 209.99,],
  [2, 4, 'Seedling', 60, 210, 240, 269.99,],
  [2, 5, 'Seedling', 60, 270, 300, 329.99,],
  [2, 6, 'Seedling', 60, 330, 360, 389.99,],
  [2, 7, 'Seedling', 60, 390, 420, 449.99,],
  [2, 8, 'Seedling', 60, 450, 480, 509.99,],
  [2, 9, 'Seedling', 60, 510, 540, 569.99,],
  [2, 10, 'Seedling', 60, 570, 600, 629.99,],
  [2, 11, 'Seedling', 60, 630, 660, 689.99,],
  [2, 12, 'Seedling', 60, 690, 720, 749.99,],
  [2, 13, 'Seedling', 60, 750, 780, 809.99,],
  [2, 14, 'Seedling', 60, 810, 840, 869.99,],
  [2, 15, 'Seedling', 60, 870, 900, 929.99,],
  [2, 16, 'Seedling', 60, 930, 960, 989.99,],
  [2, 17, 'Seedling', 60, 990, 1020, 1049.99,],
  [2, 18, 'Seedling', 60, 1050, 1080, 1109.99,],
  [2, 19, 'Seedling', 60, 1110, 1140, 1169.99,],
  [2, 20, 'Seedling', 60, 1170, 1200, 1229.99,],
  [2, 21, 'Seedling', 60, 1230, 1260, 1289.99,],
  [2, 22, 'Seedling', 60, 1290, 1320, 1349.99,],
  [2, 23, 'Seedling', 60, 1350, 1380, 1409.99,],
  [2, 24, 'Seedling', 60, 1410, 1440, 1469.99,],
  [2, 25, 'Seedling', 60, 1470, 1500, 1529.99,],
  [2, 26, 'Seedling', 60, 1530, 1560, 1589.99,],
  [2, 27, 'Seedling', 60, 1590, 1620, 1649.99,],
  [2, 28, 'Seedling', 60, 1650, 1680, 1709.99,],
  [2, 29, 'Seedling', 60, 1710, 1740, 1769.99,],
  [2, 30, 'Seedling', 60, 1770, 1800, 1829.99,],
  [2, 31, 'Seedling', 60, 1830, 1860, 1889.99,],
  [2, 32, 'Seedling', 60, 1890, 1920, 1949.99,],
  [2, 33, 'Seedling', 60, 1950, 1980, 2009.99,],
  [2, 34, 'Seedling', 60, 2010, 2040, 2069.99,],
  [2, 35, 'Seedling', 60, 2070, 2100, 2129.99,],
  [2, 36, 'Seedling', 60, 2130, 2160, 2189.99,],
  [2, 37, 'Seedling', 60, 2190, 2220, 2249.99,],
  [2, 38, 'Seedling', 60, 2250, 2280, 2309.99,],
  [2, 39, 'Seedling', 60, 2310, 2340, 2369.99,],
  [2, 40, 'Seedling', 60, 2370, 2400, 2429.99,],
  [2, 41, 'Seedling', 60, 2430, 2460, 2489.99,],
  [2, 42, 'Seedling', 60, 2490, 2520, 2549.99,],
  [2, 43, 'Seedling', 60, 2550, 2580, 2609.99,],
  [2, 44, 'Seedling', 60, 2610, 2640, 2669.99,],
  [2, 45, 'Seedling', 60, 2670, 2700, 2729.99,],
  [2, 46, 'Seedling', 60, 2730, 2760, 2789.99,],
  [2, 47, 'Seedling', 60, 2790, 2820, 2849.99,],
  [2, 48, 'Seedling', 60, 2850, 2880, 2909.99,],
  [2, 49, 'Seedling', 60, 2910, 2940, 2969.99,],
  [2, 50, 'Seedling', 60, 2970, 3000, 3029.99,],
  [2, 51, 'Seedling', 60, 3030, 3060, 3089.99,],
  [2, 52, 'Seedling', 60, 3090, 3120, 3149.99,],
  [2, 53, 'Seedling', 60, 3150, 3180, 3209.99,],
  [2, 54, 'Seedling', 60, 3210, 3240, 3269.99,],
  [2, 55, 'Seedling', 60, 3270, 3300, 3329.99,],
  [2, 56, 'Seedling', 60, 3330, 3360, 3389.99,],
  [2, 57, 'Seedling', 60, 3390, 3420, 3449.99,],
  [2, 58, 'Seedling', 60, 3450, 3480, 3509.99,],
  [2, 59, 'Seedling', 60, 3510, 3540, 3569.99,],
  [2, 60, 'Seedling', 60, 3570, 3600, 3629.99,],
  [3, 2, 'Square of seedlings', 1500, 3630, 3000, 3749.99,],
  [3, 3, 'Square of seedlings', 1500, 3750, 4500, 5249.99,],
  [3, 4, 'Square of seedlings', 1500, 5250, 6000, 6749.99,],
  [4, 5, 'Square of seedlings', 1500, 6750, 7500, 8249.99,],
  [4, 6, 'Square of seedlings', 1500, 8250, 9000, 9749.99,],
  [4, 7, 'Square of seedlings', 1500, 9750, 10500, 11249.99,],
  [4, 8, 'Square of seedlings', 1500, 11250, 12000, 12749.99,],
  [4, 9, 'Square of seedlings', 1500, 12750, 13500, 14249.99,],
  [5, 1, 'Hectare', 12000, 14250, 12000, 17999.99,],
  [5, 2, 'Hectare', 12000, 18000, 24000, 29999.99,],
  [5, 3, 'Hectare', 12000, 30000, 36000, 41999.99,],
  [5, 4, 'Hectare', 12000, 42000, 48000, 53999.99,],
  [5, 5, 'Hectare', 12000, 54000, 60000, 65999.99,],
  [5, 6, 'Hectare', 12000, 66000, 72000, 77999.99,],
  [5, 7, 'Hectare', 12000, 78000, 84000, 89999.99,],
  [5, 8, 'Hectare', 12000, 90000, 96000, 101999.99,],
  [5, 9, 'Hectare', 12000, 102000, 108000, 113999.99,],
  [5, 10, 'Hectare', 12000, 114000, 120000, 125999.99,],
  [5, 11, 'Hectare', 12000, 126000, 132000, 137999.99,],
  [5, 12, 'Hectare', 12000, 138000, 144000, 149999.99,],
  [5, 13, 'Hectare', 12000, 150000, 156000, 161999.99,],
  [5, 14, 'Hectare', 12000, 162000, 168000, 173999.99,],
  [5, 15, 'Hectare', 12000, 174000, 180000, 185999.99,],
  [5, 16, 'Hectare', 12000, 186000, 192000, 191999.99,],
  [6, 16, 'Hectare', 12000, 192000, 192000, 197999.99,],
  [6, 17, 'Hectare', 12000, 198000, 204000, 209999.99,],
  [6, 18, 'Hectare', 12000, 210000, 216000, 221999.99,],
  [6, 19, 'Hectare', 12000, 222000, 228000, 233999.99,],
  [6, 20, 'Hectare', 12000, 234000, 240000, 245999.99,],
  [6, 21, 'Hectare', 12000, 246000, 252000, 257999.99,],
  [6, 22, 'Hectare', 12000, 258000, 264000, 269999.99,],
  [6, 23, 'Hectare', 12000, 270000, 276000, 281999.99,],
  [6, 24, 'Hectare', 12000, 282000, 288000, 293999.99,],
  [6, 25, 'Hectare', 12000, 294000, 300000, 305999.99,],
  [6, 26, 'Hectare', 12000, 306000, 312000, 317999.99,],
  [6, 27, 'Hectare', 12000, 318000, 324000, 329999.99,],
  [6, 28, 'Hectare', 12000, 330000, 336000, 341999.99,],
  [6, 29, 'Hectare', 12000, 342000, 348000, 353999.99,],
  [6, 30, 'Hectare', 12000, 354000, 360000, 365999.99,],
  [6, 31, 'Hectare', 12000, 366000, 372000, 377999.99,],
  [6, 32, 'Hectare', 12000, 378000, 384000, 389999.99,],
  [6, 33, 'Hectare', 12000, 390000, 396000, 401999.99,],
  [6, 34, 'Hectare', 12000, 402000, 408000, 413999.99,],
  [6, 35, 'Hectare', 12000, 414000, 420000, 425999.99,],
  [6, 36, 'Hectare', 12000, 426000, 432000, 437999.99,],
  [6, 37, 'Hectare', 12000, 438000, 444000, 449999.99,],
  [6, 38, 'Hectare', 12000, 450000, 456000, 461999.99,],
  [6, 39, 'Hectare', 12000, 462000, 468000, 473999.99,],
  [6, 40, 'Hectare', 12000, 474000, 480000, 485999.99,],
  [6, 41, 'Hectare', 12000, 486000, 492000, 497999.99,],
  [6, 42, 'Hectare', 12000, 498000, 504000, 509999.99,],
  [6, 43, 'Hectare', 12000, 510000, 516000, 521999.99,],
  [6, 44, 'Hectare', 12000, 522000, 528000, 533999.99,],
  [6, 45, 'Hectare', 12000, 534000, 540000, 545999.99,],
  [6, 46, 'Hectare', 12000, 546000, 552000, 557999.99,],
  [6, 47, 'Hectare', 12000, 558000, 564000, 569999.99,],
  [6, 48, 'Hectare', 12000, 570000, 576000, 581999.99,],
  [6, 49, 'Hectare', 12000, 582000, 588000, 593999.99,],
  [6, 50, 'Hectare', 12000, 594000, 600000, 605999.99,],
  [6, 51, 'Hectare', 12000, 606000, 612000, 617999.99,],
  [6, 52, 'Hectare', 12000, 618000, 624000, 629999.99,],
  [6, 53, 'Hectare', 12000, 630000, 636000, 641999.99,],
  [6, 54, 'Hectare', 12000, 642000, 648000, 653999.99,],
  [6, 55, 'Hectare', 12000, 654000, 660000, 665999.99,],
  [6, 56, 'Hectare', 12000, 666000, 672000, 677999.99,],
  [6, 57, 'Hectare', 12000, 678000, 684000, 689999.99,],
  [6, 58, 'Hectare', 12000, 690000, 696000, 701999.99,],
  [6, 59, 'Hectare', 12000, 702000, 708000, 713999.99,],
  [6, 60, 'Hectare', 12000, 714000, 720000, 725999.99,],
  [6, 61, 'Hectare', 12000, 726000, 732000, 737999.99,],
  [6, 62, 'Hectare', 12000, 738000, 744000, 749999.99,],
  [6, 63, 'Hectare', 12000, 750000, 756000, 761999.99,],
  [6, 64, 'Hectare', 12000, 762000, 768000, 774000,],
]

export default {
  props: {
    count: {
      type: Number,
      required: false,
      default: 0
    },
    target: {
      type: String,
      required: false,
      default: ''
    },
    size: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data: function() {
    return {
      canvas: null,
      ctx: null,
      bump: 1,
      painting: false,
      currentCount: null,
      currentTarget: ''
    }
  },
  computed: {
    width() {
      switch (this.currentTarget) {
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
      switch (this.currentTarget) {
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
    portrait() {
      return this.currentTarget === 'Instagram'
    },
    image: function() {
      return this.getImage(this.currentCount)
    },
    fontSize() {
      let ret = null

      // We need to adjust the font sizes a bit based on locale.
      const locale = this.lang ? this.$lang.getLocale() : 'en-UK'

      if (this.portrait) {
        switch (this.currentTarget) {
          case 'Instagram':
            ret = 55
          case 'Facebook':
            ret = 40
          case 'Twitter':
            ret = 52
          case 'LinkedIn':
            ret = 40
        }

        if (locale === 'fr' || locale === 'fr-BE') {
          ret = Math.round(ret * 7 / 6)
        } else if (locale === 'en') {
          ret = Math.round(ret * 7 / 6)
        }
      } else {
        switch (this.currentTarget) {
          case 'Instagram':
            ret = 110
          case 'Facebook':
            ret = 50
          case 'Twitter':
            ret = 65
          case 'LinkedIn':
            ret = 45
        }

        if (locale === 'fr' || locale === 'fr-BE') {
          if (this.currentTarget !== 'Twitter') {
            ret = Math.round(ret * 6 / 7)
          } else {
            ret = Math.round(ret * 7 / 6)
          }
        } else if (locale === 'en') {
          if (this.currentTarget === 'Twitter') {
            ret = Math.round(ret * 4 / 3)
          }
        }
      }

      return ret
    },
    smallerFontSize() {
      return Math.round(this.fontSize * 4 / 5)
    },
    initialY() {
      switch (this.currentTarget) {
        case 'Instagram':
          return 100
        case 'Facebook':
          return this.canvas.height / 5
        case 'Twitter':
          return this.canvas.height / 5
        case 'LinkedIn':
          return this.canvas.height / 5
      }
    },
    initialX() {
      return this.portrait ? 0 : this.canvas.width / 20
    }
  },
  watch: {
    count: {
      handler: function(newVal) {
        this.currentCount = newVal
      },
      immediate: true
    },
    target: {
      handler: function(newVal) {
        this.currentTarget = newVal
      },
      immediate: true
    },
    currentTarget() {
      console.log('Target changed, paint')
      this.paint()
    },
    currentCount() {
      console.log('Count changed, paint')
      this.paint()
    },
    painting: function(newVal) {
      this.$emit('update:painting', newVal)
    }
  },
  mounted() {
    const _paq = window._paq = window._paq || [];
    _paq.push(['trackEvent', 'ShareStats', 'ClickedOnButton']);
    console.log('Mounted', this.$props)
    this.paint()
  },
  methods: {
    rangeIndex(count) {
      count = parseInt(count)
      let ix = 1

      while (ix < RANGES.length && count > RANGES[ix][6]) {
        ix++
      }

      console.log('Count => ix', count, ix, RANGES[ix])
      return ix
    },
    getCount(count) {
      const ix = this.rangeIndex(count)

      if (RANGES[ix][2] === 'Square of seedlings') {
        // We want to show the number of seedlings, not the number of squares.
        return Math.round(count / 60)
      } else {
        return RANGES[ix][1]
      }
    },
    getImage(thecount) {
      let ret = null

      const ix = this.rangeIndex(thecount)
      console.log('Tree/hect count', thecount, ix)

      if (ix) {
        const SLOT = RANGES[ix]
        ret = 'ImpactRange' + SLOT[0]

        if (this.portrait) {
          ret += 'Square'
        } else {
          ret += 'Landscape'
        }

        ret += '-' + SLOT[1] + '.png'
      }

      console.log('Image is', thecount, ret)
      return ret
    },
    download() {
      try {
        const _paq = window._paq = window._paq || [];
        _paq.push(['trackEvent', 'ShareStats', 'Download', this.currentTarget]);

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
          let ctx = this.ctx
          ctx.font = "bold " + this.fontSize + "px Asap, sans-serif"

          // Add background.
          this.insertImage(this.image, 0, 0, this.width, this.height, function() {
            let x = this.initialX
            let y = this.initialY

            // Get length of the whole line including the kg value.
            let str = parseInt(this.currentCount).toLocaleString() + ' kg'
            let text = ''

            // Use the line height of this as our standard for moving down the image.
            let lineHeight = ctx.measureText(
                this.$lang.get('partials.share_modal_weve_saved') + str + this.$lang.get('partials.share_modal_of_co2')
            ).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2

            let wholeline

            if (this.portrait) {
              wholeline = this.$lang.get('partials.share_modal_weve_saved') + str + this.$lang.get('partials.share_modal_of_co2')
              x = this.fillCentredText(this.$lang.get('partials.share_modal_weve_saved') + ' ', x, y, wholeline)
              x = this.fillWhiteBlackBox(str, x, y)
              x = this.fillText(' ' + this.$lang.get('partials.share_modal_of_co2'), x, y)

              y += lineHeight

              wholeline = this.$lang.get('partials.share_modal_by_repairing') + ' ' + this.$lang.get('partials.share_modal_broken_stuff')
              x = this.fillCentredText(this.$lang.get('partials.share_modal_by_repairing') + ' ', x, y, wholeline)
              x = this.fillText(this.$lang.get('partials.share_modal_broken_stuff'), x, y)
            } else {
              x = this.fillText(this.$lang.get('partials.share_modal_weve_saved') + ' ', x, y)
              x = this.fillWhiteBlackBox(str, x, y)

              y += lineHeight
              x = this.initialX

              x = this.fillText(this.$lang.get('partials.share_modal_of_co2'), x, y)
              x = this.fillText(' ' + this.$lang.get('partials.share_modal_by_repairing'), x, y)

              y += lineHeight
              x = this.initialX

              x = this.fillText(this.$lang.get('partials.share_modal_broken_stuff'), x, y)
            }

            // Wavy divider line.
            if (this.portrait) {
              y += lineHeight / 2
            } else {
              if (this.currentTarget === 'Twitter') {
                y = this.height / 2 - 39 + lineHeight / 2
              } else {
                y = this.height / 2 - 39 / 4
              }
            }

            if (this.portrait) {
              x = (canvas.width - 292 / 2) / 2
            } else {
              x = this.initialX
            }

            this.insertImage('WavyDividerLine.png', x, y, 292 / 2, 39 / 2)

            if (this.portrait) {
              y += lineHeight + 39 / 4
            } else {
              if (this.currentTarget === 'Twitter') {
                y += lineHeight + 39
              } else {
                y += lineHeight + 39
              }
            }

            const ix = this.rangeIndex(this.currentCount)
            str = this.getCount(this.currentCount).toLocaleString()

            ctx.font = "bold " + this.smallerFontSize + "px Asap, sans-serif"

            lineHeight = ctx.measureText(
                this.$lang.get('partials.share_modal_weve_saved') + str + this.$lang.get('partials.share_modal_of_co2')
            ).emHeightAscent + ctx.measureText(str).emHeightDescent + MARG * 2

            if (RANGES[ix][2] != 'Hectare') {
              if (this.portrait) {
                wholeline = this.$lang.get('partials.share_modal_thats_like') + ' ' +
                    this.$lang.get('partials.share_modal_growing_about') + ' '
                str
                x = this.fillCentredText(this.$lang.get('partials.share_modal_thats_like') + ' ', x, y, wholeline)
                x = this.fillText(this.$lang.get('partials.share_modal_growing_about') + ' ', x, y)
                x = this.fillWhiteBlackBox(str, x, y)
                y += lineHeight
                x = this.initialX
                wholeline = this.$lang.choice('partials.share_modal_seedlings', str)
                x = this.fillCentredText(this.$lang.choice('partials.share_modal_seedlings', str), x, y, wholeline)
              } else {
                x = this.fillText(this.$lang.get('partials.share_modal_thats_like'), x, y)
                y += lineHeight
                x = this.initialX
                x = this.fillText(this.$lang.get('partials.share_modal_growing_about') + ' ', x, y)
                x = this.fillWhiteBlackBox(str, x, y)
                y += lineHeight
                x = this.initialX
                x = this.fillText(this.$lang.choice('partials.share_modal_seedlings', str), x, y)
              }
            } else {
              if (this.portrait) {
                wholeline = this.$lang.get('partials.share_modal_thats_like') + ' ' +
                    this.$lang.get('partials.share_modal_planting_around') + ' '
                str
                x = this.fillCentredText(this.$lang.get('partials.share_modal_thats_like') + ' ', x, y, wholeline)
                x = this.fillText(this.$lang.get('partials.share_modal_planting_around') + ' ', x, y)
                x = this.fillWhiteBlackBox(str, x, y)
                y += lineHeight
                x = this.initialX
                wholeline = this.$lang.choice('partials.share_modal_hectares', str)
                x = this.fillCentredText(this.$lang.choice('partials.share_modal_hectares', str), x, y, wholeline)
              } else {
                x = this.fillText(this.$lang.get('partials.share_modal_thats_like'), x, y)
                y += lineHeight
                x = this.initialX
                x = this.fillText(this.$lang.get('partials.share_modal_planting_around') + ' ', x, y)
                x = this.fillWhiteBlackBox(str, x, y)
                y += lineHeight
                x = this.initialX
                x = this.fillText(this.$lang.choice('partials.share_modal_hectares', str), x, y)
              }
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
    fillText(str, x, y, colour) {
      const ctx = this.ctx
      console.log('text', str, ctx.font)

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
      img.src = '/images/stats/' + name
      img.onload = () => {
        ctx.drawImage(img, x, y, width, height)

        if (cb) {
          setTimeout(cb.bind(this), 500)
        }
      }
    },
    next() {
      let currentImage = this.getImage(this.currentCount)

      while (currentImage === this.getImage(this.currentCount)) {
        this.currentCount++
      }

      this.paint()
    },
    prev() {
      let currentImage = this.getImage(this.currentCount)

      while (currentImage === this.getImage(this.currentCount)) {
        this.currentCount--
      }

      this.paint()
    },
    setCount(count) {
      console.log('Set count', count)
      this.currentCount = count
      console.log('Set ok')
    },
    setTarget(target) {
      this.currentTarget = target
    }
  }
}
</script>
<style scoped>
.canvas {
  max-width: 100%;
}
</style>