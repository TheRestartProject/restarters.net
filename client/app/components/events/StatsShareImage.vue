<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStatsShareImage } from '../../composables/useStatsShareImage.js'

// Port of resources/js/components/StatsShare.vue: paints a shareable social-
// media image (a CO2e headline + a seedling/hectare visualisation) onto a
// <canvas>, for the platform-specific dimensions/font-sizing StatsShareModal
// selects. Reused by both the event and group "Share this" buttons (see
// StatsShareImageModal.vue) - the original StatsShare.vue/StatsShareModal.vue
// pair was likewise shared, unchanged, between StatsImpact.vue's event and
// group usage; only the `count` prop differs between callers.
//
// The CO2e -> background-image lookup table and the per-platform dimension/
// font-size math (the parts most worth protecting with tests, since a
// screenshot diff can't reliably catch off-by-one text positioning) live in
// composables/useStatsShareImage.js, unit-tested directly there. This
// component's own canvas-drawing code - the actual fillText/measureText
// positioning - is not practically unit-testable (canvas 2D contexts aren't
// implemented in the happy-dom test environment) and has not been visually
// verified pixel-for-pixel against develop; see the PR description.
const props = defineProps({
  count: {
    type: Number,
    required: false,
    default: 0,
  },
  target: {
    type: String,
    required: false,
    default: '',
  },
  // Shows the "This image is WxH pixels" caption under the canvas.
  size: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const emit = defineEmits(['update:painting'])

const { t, locale } = useI18n()
const { getImage, getCount, dimensions, initialX, initialY, fontSize, smallerFontSize, rangeForCount } =
  useStatsShareImage()

const MARGIN = 10
const RADIUS = 8

const canvasRef = ref(null)
const painting = ref(false)
// Forces the <canvas> to remount between paints (StatsShare.vue's `bump`) -
// canvas drawing state doesn't reliably clear otherwise when width/height
// change between platform targets.
const bump = ref(1)

const size = computed(() => dimensions(props.target) || { width: 0, height: 0 })
const width = computed(() => size.value.width)
const height = computed(() => size.value.height)
const portrait = computed(() => props.target === 'Instagram')

function setPainting(value) {
  painting.value = value
  emit('update:painting', value)
}

function fillText(ctx, str, x, y, colour) {
  ctx.fillStyle = colour || 'black'
  ctx.strokeStyle = colour || 'black'
  ctx.fillText(str, x, y)
  return x + ctx.measureText(str).width
}

// x is computed here, never taken from the caller - it used to be a
// parameter that was overwritten on the first line.
function fillCentredText(ctx, canvasEl, text, y, wholeLine) {
  const length = ctx.measureText(wholeLine || text).width
  const centredX = portrait.value ? (canvasEl.width - length) / 2 : canvasEl.width / 40
  return fillText(ctx, text, centredX, y)
}

// The white-on-black "pill" behind a headline number (e.g. the kg figure).
function fillWhiteBlackBox(ctx, str, x, y) {
  const metrics = ctx.measureText(str)
  const ascent = metrics.emHeightAscent ?? metrics.actualBoundingBoxAscent ?? 0
  const descent = metrics.emHeightDescent ?? metrics.actualBoundingBoxDescent ?? 0

  if (ctx.roundRect) {
    ctx.roundRect(x, y - ascent - MARGIN, metrics.width + MARGIN * 2, ascent + descent + MARGIN * 2, RADIUS)
  } else {
    // Older browsers without roundRect().
    ctx.rect(x, y - ascent - MARGIN, metrics.width + MARGIN * 2, ascent + descent + MARGIN * 2)
  }
  ctx.fill()
  // Without beginPath(), a later roundRect() call re-fills this same
  // rectangle path too, painting over what's already been written.
  ctx.beginPath()

  x += MARGIN
  x = fillText(ctx, str, x, y, 'white')
  x += MARGIN
  return x
}

function lineHeight(ctx, sampleLine, sampleValue) {
  const measure1 = ctx.measureText(sampleLine)
  const measure2 = ctx.measureText(sampleValue)
  const asc = measure1.emHeightAscent ?? measure1.actualBoundingBoxAscent ?? 0
  const desc = measure2.emHeightDescent ?? measure2.actualBoundingBoxDescent ?? 0
  return asc + desc + MARGIN * 2
}

// StatsShare.vue served these from Laravel's own public/images/stats/
// (same-origin, since the legacy app served the frontend and API from one
// origin). Under Nuxt the client and API are different origins, and
// public/images/stats/ is a plain static file outside config/cors.php's
// 'api/*' scope - drawing a cross-origin image onto a <canvas> without CORS
// permission taints it, and a tainted canvas throws on toDataURL()/
// toBlob(), which is exactly what download() needs. Routing through
// GET /api/v2/stats/share-image/{filename} (StatsShareImageController)
// instead of copying the ~224MB image set into the client gets both: CORS
// headers (so crossOrigin="anonymous" below succeeds) and zero extra client
// bytes.
const runtimeConfig = useRuntimeConfig()
function shareImageUrl(name) {
  return `${runtimeConfig.public.apiBase}/api/v2/stats/share-image/${name}`
}

// Loads a background/decoration image and draws it - StatsShare.vue's
// insertImage(), converted from a callback into a promise. The 500ms pause
// after onload is inherited unchanged from develop (a canvas-fettling delay
// the original comments call "not entirely synchronous"); not re-derived
// here, just preserved.
function insertImage(ctx, name, x, y, w, h) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    // Required for the canvas to stay "un-tainted" across the cross-origin
    // load above - without this, drawImage() still succeeds (nothing stops
    // a plain <img> loading cross-origin) but the canvas becomes unreadable
    // and download()'s toDataURL() throws a SecurityError.
    img.crossOrigin = 'anonymous'
    img.onload = () => {
      ctx.drawImage(img, x, y, w, h)
      setTimeout(resolve, 500)
    }
    img.onerror = () => reject(new Error(`Failed to load ${name}`))
    img.src = shareImageUrl(name)
  })
}

async function paint() {
  if (painting.value) {
    return
  }

  setPainting(true)
  bump.value++
  await nextTick()

  const canvasEl = canvasRef.value
  const ctx = canvasEl?.getContext ? canvasEl.getContext('2d') : null

  if (!ctx) {
    // No 2D canvas support (or not yet mounted) - nothing to draw.
    setPainting(false)
    return
  }

  try {
    const image = getImage(props.count, props.target)
    await insertImage(ctx, image, 0, 0, width.value, height.value)

    const fSize = fontSize(props.target, locale.value)
    const smallSize = smallerFontSize(props.target, locale.value)
    ctx.font = `bold ${fSize}px Asap, sans-serif`

    let x = initialX(props.target)
    let y = initialY(props.target)

    const kgStr = `${parseInt(props.count, 10).toLocaleString()} kg`
    const savedLine = t('partials.share_modal_weve_saved') + kgStr + t('partials.share_modal_of_co2')
    let height1 = lineHeight(ctx, savedLine, kgStr)

    if (portrait.value) {
      const wholeline = t('partials.share_modal_weve_saved') + kgStr + t('partials.share_modal_of_co2')
      x = fillCentredText(ctx, canvasEl, `${t('partials.share_modal_weve_saved')} `, y, wholeline)
      x = fillWhiteBlackBox(ctx, kgStr, x, y)
      x = fillText(ctx, ` ${t('partials.share_modal_of_co2')}`, x, y)

      y += height1

      const repairLine = `${t('partials.share_modal_by_repairing')} ${t('partials.share_modal_broken_stuff')}`
      x = fillCentredText(ctx, canvasEl, `${t('partials.share_modal_by_repairing')} `, y, repairLine)
      x = fillText(ctx, t('partials.share_modal_broken_stuff'), x, y)
    } else {
      x = fillText(ctx, `${t('partials.share_modal_weve_saved')} `, x, y)
      x = fillWhiteBlackBox(ctx, kgStr, x, y)

      y += height1
      x = initialX(props.target)

      x = fillText(ctx, t('partials.share_modal_of_co2'), x, y)
      x = fillText(ctx, ` ${t('partials.share_modal_by_repairing')}`, x, y)

      y += height1
      x = initialX(props.target)

      x = fillText(ctx, t('partials.share_modal_broken_stuff'), x, y)
    }

    // Wavy divider line.
    if (portrait.value) {
      y += height1 / 2
      x = (canvasEl.width - 292 / 2) / 2
    } else {
      y = props.target === 'Twitter' ? height.value / 2 - 39 + height1 / 2 : height.value / 2 - 39 / 4
      x = initialX(props.target)
    }

    await insertImage(ctx, 'WavyDividerLine.png', x, y, 292 / 2, 39 / 2)

    y += portrait.value ? height1 + 39 / 4 : height1 + 39

    const range = rangeForCount(props.count)
    const numericCount = getCount(props.count)
    const countStr = numericCount.toLocaleString()

    ctx.font = `bold ${smallSize}px Asap, sans-serif`
    const smallSavedLine = t('partials.share_modal_weve_saved') + countStr + t('partials.share_modal_of_co2')
    height1 = lineHeight(ctx, smallSavedLine, countStr)

    x = initialX(props.target)

    if (range.type !== 'Hectare') {
      if (portrait.value) {
        const wholeline = `${t('partials.share_modal_thats_like')} ${t('partials.share_modal_growing_about')} `
        x = fillCentredText(ctx, canvasEl, `${t('partials.share_modal_thats_like')} `, y, wholeline)
        x = fillText(ctx, `${t('partials.share_modal_growing_about')} `, x, y)
        x = fillWhiteBlackBox(ctx, countStr, x, y)
        y += height1
        x = initialX(props.target)
        const seedlingsLine = t('partials.share_modal_seedlings', { count: numericCount }, numericCount)
        x = fillCentredText(ctx, canvasEl, seedlingsLine, y, seedlingsLine)
      } else {
        x = fillText(ctx, t('partials.share_modal_thats_like'), x, y)
        y += height1
        x = initialX(props.target)
        x = fillText(ctx, `${t('partials.share_modal_growing_about')} `, x, y)
        x = fillWhiteBlackBox(ctx, countStr, x, y)
        y += height1
        x = initialX(props.target)
        x = fillText(ctx, t('partials.share_modal_seedlings', { count: numericCount }, numericCount), x, y)
      }
    } else {
      if (portrait.value) {
        const wholeline = `${t('partials.share_modal_thats_like')} ${t('partials.share_modal_planting_around')} `
        x = fillCentredText(ctx, canvasEl, `${t('partials.share_modal_thats_like')} `, y, wholeline)
        x = fillText(ctx, `${t('partials.share_modal_planting_around')} `, x, y)
        x = fillWhiteBlackBox(ctx, countStr, x, y)
        y += height1
        x = initialX(props.target)
        const hectaresLine = t('partials.share_modal_hectares', { count: numericCount }, numericCount)
        x = fillCentredText(ctx, canvasEl, hectaresLine, y, hectaresLine)
      } else {
        x = fillText(ctx, t('partials.share_modal_thats_like'), x, y)
        y += height1
        x = initialX(props.target)
        x = fillText(ctx, `${t('partials.share_modal_planting_around')} `, x, y)
        x = fillWhiteBlackBox(ctx, countStr, x, y)
        y += height1
        x = initialX(props.target)
        x = fillText(ctx, t('partials.share_modal_hectares', { count: numericCount }, numericCount), x, y)
      }
    }
  } catch (e) {
    console.error('StatsShareImage paint error', e)
  }

  // StatsShare.vue's own comment: "Canvas fettling is not entirely
  // synchronous, so you can get weird artifacts if you switch buttons too
  // rapidly. No easy way to fix this entirely, but this will help a lot."
  setTimeout(() => setPainting(false), 2000)
}

function download() {
  const canvasEl = canvasRef.value
  if (!canvasEl) {
    return
  }

  try {
    const link = document.createElement('a')
    link.download = 'stats.png'
    link.href = canvasEl.toDataURL()
    link.click()
  } catch (e) {
    console.error('Failed to download', e)
  }
}

watch([() => props.count, () => props.target], () => paint())
// StatsShare.vue's mounted() paints eagerly too - StatsShareImageModal.vue's
// own @shown handler also calls paint() (the canvas can go stale while the
// modal is hidden), so both fire; paint() no-ops if already in progress.
onMounted(() => paint())

defineExpose({ paint, download })
</script>

<template>
  <div data-testid="stats-share-image">
    <p v-if="size" class="text-muted small">
      This image is {{ width }}x{{ height }} pixels.
    </p>
    <div class="d-flex justify-content-around w-100">
      <canvas :key="`canvas-${bump}`" ref="canvasRef" :width="width" :height="height" class="canvas" />
    </div>
  </div>
</template>

<style scoped>
.canvas {
  max-width: 100%;
}
</style>
