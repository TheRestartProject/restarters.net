// Vanilla-JS port of the old Vue 2 resources/js/components/StatsShare.vue.
//
// This is the canvas-based "we've saved N kg CO2e" social-share image generator
// used by the partner-facing widget at /outbound/info/{type}/{id}/leaf
// (rendered by resources/views/partials/visualisations/leaf.blade.php). That
// route is embedded live in <iframe>s on therestartproject.org and partner
// sites, and must keep working after the Vue 2 tree under resources/js is
// deleted at cutover, so it lives here under resources/global/js instead.
//
// Translation strings are baked into data-t-* attributes by the Blade partial
// (via __()/trans_choice()) rather than looked up at runtime, since the Vite
// translations plugin that powered the old component's this.__() also dies
// at cutover.
//
// Ported behaviour is intentionally bug-for-bug compatible with the Vue
// component that currently ships in production - see the comments on
// fontSize() and fillWhiteBlackBox() below for the two quirks this
// preserves rather than "fixes".

const MARG = 10
const RADIUS = 8

// This is a lookup table which determines which visualisation we use (seedling, square of seedlings, hectares), and
// which variant of it. Row 0 is the column header, kept only so the row indices below line up with the source table.
const RANGES = [
  ['Visualisation level', 'Increment number', 'Increment type', 'CO2e per increment (kg)', 'Lower boundary (kg CO2e)', 'Exact CO2e represented', 'Upper boundary (kg CO2e)'],
  [1, 1, 'Seedling', 60, 30, 60, 89.99],
  [1, 2, 'Seedling', 60, 90, 120, 149.99],
  [1, 3, 'Seedling', 60, 150, 180, 209.99],
  [2, 4, 'Seedling', 60, 210, 240, 269.99],
  [2, 5, 'Seedling', 60, 270, 300, 329.99],
  [2, 6, 'Seedling', 60, 330, 360, 389.99],
  [2, 7, 'Seedling', 60, 390, 420, 449.99],
  [2, 8, 'Seedling', 60, 450, 480, 509.99],
  [2, 9, 'Seedling', 60, 510, 540, 569.99],
  [2, 10, 'Seedling', 60, 570, 600, 629.99],
  [2, 11, 'Seedling', 60, 630, 660, 689.99],
  [2, 12, 'Seedling', 60, 690, 720, 749.99],
  [2, 13, 'Seedling', 60, 750, 780, 809.99],
  [2, 14, 'Seedling', 60, 810, 840, 869.99],
  [2, 15, 'Seedling', 60, 870, 900, 929.99],
  [2, 16, 'Seedling', 60, 930, 960, 989.99],
  [2, 17, 'Seedling', 60, 990, 1020, 1049.99],
  [2, 18, 'Seedling', 60, 1050, 1080, 1109.99],
  [2, 19, 'Seedling', 60, 1110, 1140, 1169.99],
  [2, 20, 'Seedling', 60, 1170, 1200, 1229.99],
  [2, 21, 'Seedling', 60, 1230, 1260, 1289.99],
  [2, 22, 'Seedling', 60, 1290, 1320, 1349.99],
  [2, 23, 'Seedling', 60, 1350, 1380, 1409.99],
  [2, 24, 'Seedling', 60, 1410, 1440, 1469.99],
  [2, 25, 'Seedling', 60, 1470, 1500, 1529.99],
  [2, 26, 'Seedling', 60, 1530, 1560, 1589.99],
  [2, 27, 'Seedling', 60, 1590, 1620, 1649.99],
  [2, 28, 'Seedling', 60, 1650, 1680, 1709.99],
  [2, 29, 'Seedling', 60, 1710, 1740, 1769.99],
  [2, 30, 'Seedling', 60, 1770, 1800, 1829.99],
  [2, 31, 'Seedling', 60, 1830, 1860, 1889.99],
  [2, 32, 'Seedling', 60, 1890, 1920, 1949.99],
  [2, 33, 'Seedling', 60, 1950, 1980, 2009.99],
  [2, 34, 'Seedling', 60, 2010, 2040, 2069.99],
  [2, 35, 'Seedling', 60, 2070, 2100, 2129.99],
  [2, 36, 'Seedling', 60, 2130, 2160, 2189.99],
  [2, 37, 'Seedling', 60, 2190, 2220, 2249.99],
  [2, 38, 'Seedling', 60, 2250, 2280, 2309.99],
  [2, 39, 'Seedling', 60, 2310, 2340, 2369.99],
  [2, 40, 'Seedling', 60, 2370, 2400, 2429.99],
  [2, 41, 'Seedling', 60, 2430, 2460, 2489.99],
  [2, 42, 'Seedling', 60, 2490, 2520, 2549.99],
  [2, 43, 'Seedling', 60, 2550, 2580, 2609.99],
  [2, 44, 'Seedling', 60, 2610, 2640, 2669.99],
  [2, 45, 'Seedling', 60, 2670, 2700, 2729.99],
  [2, 46, 'Seedling', 60, 2730, 2760, 2789.99],
  [2, 47, 'Seedling', 60, 2790, 2820, 2849.99],
  [2, 48, 'Seedling', 60, 2850, 2880, 2909.99],
  [2, 49, 'Seedling', 60, 2910, 2940, 2969.99],
  [2, 50, 'Seedling', 60, 2970, 3000, 3029.99],
  [2, 51, 'Seedling', 60, 3030, 3060, 3089.99],
  [2, 52, 'Seedling', 60, 3090, 3120, 3149.99],
  [2, 53, 'Seedling', 60, 3150, 3180, 3209.99],
  [2, 54, 'Seedling', 60, 3210, 3240, 3269.99],
  [2, 55, 'Seedling', 60, 3270, 3300, 3329.99],
  [2, 56, 'Seedling', 60, 3330, 3360, 3389.99],
  [2, 57, 'Seedling', 60, 3390, 3420, 3449.99],
  [2, 58, 'Seedling', 60, 3450, 3480, 3509.99],
  [2, 59, 'Seedling', 60, 3510, 3540, 3569.99],
  [2, 60, 'Seedling', 60, 3570, 3600, 3629.99],
  [3, 2, 'Square of seedlings', 1500, 3630, 3000, 3749.99],
  [3, 3, 'Square of seedlings', 1500, 3750, 4500, 5249.99],
  [3, 4, 'Square of seedlings', 1500, 5250, 6000, 6749.99],
  [4, 5, 'Square of seedlings', 1500, 6750, 7500, 8249.99],
  [4, 6, 'Square of seedlings', 1500, 8250, 9000, 9749.99],
  [4, 7, 'Square of seedlings', 1500, 9750, 10500, 11249.99],
  [4, 8, 'Square of seedlings', 1500, 11250, 12000, 12749.99],
  [4, 9, 'Square of seedlings', 1500, 12750, 13500, 14249.99],
  [5, 1, 'Hectare', 12000, 14250, 12000, 17999.99],
  [5, 2, 'Hectare', 12000, 18000, 24000, 29999.99],
  [5, 3, 'Hectare', 12000, 30000, 36000, 41999.99],
  [5, 4, 'Hectare', 12000, 42000, 48000, 53999.99],
  [5, 5, 'Hectare', 12000, 54000, 60000, 65999.99],
  [5, 6, 'Hectare', 12000, 66000, 72000, 77999.99],
  [5, 7, 'Hectare', 12000, 78000, 84000, 89999.99],
  [5, 8, 'Hectare', 12000, 90000, 96000, 101999.99],
  [5, 9, 'Hectare', 12000, 102000, 108000, 113999.99],
  [5, 10, 'Hectare', 12000, 114000, 120000, 125999.99],
  [5, 11, 'Hectare', 12000, 126000, 132000, 137999.99],
  [5, 12, 'Hectare', 12000, 138000, 144000, 149999.99],
  [5, 13, 'Hectare', 12000, 150000, 156000, 161999.99],
  [5, 14, 'Hectare', 12000, 162000, 168000, 173999.99],
  [5, 15, 'Hectare', 12000, 174000, 180000, 185999.99],
  [5, 16, 'Hectare', 12000, 186000, 192000, 191999.99],
  [6, 16, 'Hectare', 12000, 192000, 192000, 197999.99],
  [6, 17, 'Hectare', 12000, 198000, 204000, 209999.99],
  [6, 18, 'Hectare', 12000, 210000, 216000, 221999.99],
  [6, 19, 'Hectare', 12000, 222000, 228000, 233999.99],
  [6, 20, 'Hectare', 12000, 234000, 240000, 245999.99],
  [6, 21, 'Hectare', 12000, 246000, 252000, 257999.99],
  [6, 22, 'Hectare', 12000, 258000, 264000, 269999.99],
  [6, 23, 'Hectare', 12000, 270000, 276000, 281999.99],
  [6, 24, 'Hectare', 12000, 282000, 288000, 293999.99],
  [6, 25, 'Hectare', 12000, 294000, 300000, 305999.99],
  [6, 26, 'Hectare', 12000, 306000, 312000, 317999.99],
  [6, 27, 'Hectare', 12000, 318000, 324000, 329999.99],
  [6, 28, 'Hectare', 12000, 330000, 336000, 341999.99],
  [6, 29, 'Hectare', 12000, 342000, 348000, 353999.99],
  [6, 30, 'Hectare', 12000, 354000, 360000, 365999.99],
  [6, 31, 'Hectare', 12000, 366000, 372000, 377999.99],
  [6, 32, 'Hectare', 12000, 378000, 384000, 389999.99],
  [6, 33, 'Hectare', 12000, 390000, 396000, 401999.99],
  [6, 34, 'Hectare', 12000, 402000, 408000, 413999.99],
  [6, 35, 'Hectare', 12000, 414000, 420000, 425999.99],
  [6, 36, 'Hectare', 12000, 426000, 432000, 437999.99],
  [6, 37, 'Hectare', 12000, 438000, 444000, 449999.99],
  [6, 38, 'Hectare', 12000, 450000, 456000, 461999.99],
  [6, 39, 'Hectare', 12000, 462000, 468000, 473999.99],
  [6, 40, 'Hectare', 12000, 474000, 480000, 485999.99],
  [6, 41, 'Hectare', 12000, 486000, 492000, 497999.99],
  [6, 42, 'Hectare', 12000, 498000, 504000, 509999.99],
  [6, 43, 'Hectare', 12000, 510000, 516000, 521999.99],
  [6, 44, 'Hectare', 12000, 522000, 528000, 533999.99],
  [6, 45, 'Hectare', 12000, 534000, 540000, 545999.99],
  [6, 46, 'Hectare', 12000, 546000, 552000, 557999.99],
  [6, 47, 'Hectare', 12000, 558000, 564000, 569999.99],
  [6, 48, 'Hectare', 12000, 570000, 576000, 581999.99],
  [6, 49, 'Hectare', 12000, 582000, 588000, 593999.99],
  [6, 50, 'Hectare', 12000, 594000, 600000, 605999.99],
  [6, 51, 'Hectare', 12000, 606000, 612000, 617999.99],
  [6, 52, 'Hectare', 12000, 618000, 624000, 629999.99],
  [6, 53, 'Hectare', 12000, 630000, 636000, 641999.99],
  [6, 54, 'Hectare', 12000, 642000, 648000, 653999.99],
  [6, 55, 'Hectare', 12000, 654000, 660000, 665999.99],
  [6, 56, 'Hectare', 12000, 666000, 672000, 677999.99],
  [6, 57, 'Hectare', 12000, 678000, 684000, 689999.99],
  [6, 58, 'Hectare', 12000, 690000, 696000, 701999.99],
  [6, 59, 'Hectare', 12000, 702000, 708000, 713999.99],
  [6, 60, 'Hectare', 12000, 714000, 720000, 725999.99],
  [6, 61, 'Hectare', 12000, 726000, 732000, 737999.99],
  [6, 62, 'Hectare', 12000, 738000, 744000, 749999.99],
  [6, 63, 'Hectare', 12000, 750000, 756000, 761999.99],
  [6, 64, 'Hectare', 12000, 762000, 768000, 774000],
]

export function rangeIndex(count) {
  count = parseInt(count, 10)
  let ix = 1

  while (ix < RANGES.length && count > RANGES[ix][6]) {
    ix++
  }

  return ix
}

export function getCount(count) {
  const ix = rangeIndex(count)

  if (RANGES[ix][2] === 'Square of seedlings') {
    // We want to show the number of seedlings, not the number of squares.
    return Math.round(count / 60)
  }

  return RANGES[ix][1]
}

export function isHectareRange(count) {
  return RANGES[rangeIndex(count)][2] === 'Hectare'
}

export function getImage(count, portrait) {
  const slot = RANGES[rangeIndex(count)]
  return 'ImpactRange' + slot[0] + (portrait ? 'Square' : 'Landscape') + '-' + slot[1] + '.png'
}

const DIMENSIONS = {
  Instagram: { width: 1080, height: 1080 },
  Facebook: { width: 1200, height: 630 },
  Twitter: { width: 1600, height: 900 },
  LinkedIn: { width: 1200, height: 627 },
}

export function isPortrait(target) {
  return target === 'Instagram'
}

// Faithful port of the Vue component's `fontSize` computed property, including its
// bug: none of its switch cases `break`, so whichever case matches, every case below
// it also runs, and the value that sticks is always the LAST case's (LinkedIn), not
// the matched target's own size - e.g. target 'Facebook' resolves to the 'LinkedIn'
// size (45 landscape / 40 portrait), not the 50/40 written against 'Facebook'. That's
// what's actually shipping in production today, so it's preserved here rather than
// corrected, to keep the ported widget pixel-identical to the component it replaces.
export function fontSize(target, portrait) {
  let ret = null

  if (portrait) {
    switch (target) {
      case 'Instagram': ret = 55
      case 'Facebook': ret = 40
      case 'Twitter': ret = 52
      case 'LinkedIn': ret = 40
    }
  } else {
    switch (target) {
      case 'Instagram': ret = 110
      case 'Facebook': ret = 50
      case 'Twitter': ret = 65
      case 'LinkedIn': ret = 45
    }
  }

  // The Vue original also adjusted this for locale, but gated it on `this.lang`
  // (no `$`) - a property the component never actually defined - so that branch
  // never fired in production. There is no locale adjustment to port.
  return ret
}

export function smallerFontSize(target, portrait) {
  return Math.round(fontSize(target, portrait) * 4 / 5)
}

// Matches the Vue original's default pluralisation rule (count === 1 selects the
// first `|`-separated segment, otherwise the last) for the two count-based strings
// this widget uses (share_modal_seedlings, share_modal_hectares), which have no
// {n}/[n,m] qualifiers. The Blade partial resolves each segment's localised text
// server-side (trans_choice(key, 1) / trans_choice(key, 2)) and passes both in;
// this just picks between them the same way the Vue mixin did.
export function pluralText(count, singular, plural) {
  return count === 1 ? singular : plural
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('Failed to load image: ' + src))
    img.src = src
  })
}

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

function ascKey(measure) {
  return measure.emHeightAscent ? 'emHeightAscent' : 'actualBoundingBoxAscent'
}

function descKey(measure) {
  return measure.emHeightDescent ? 'emHeightDescent' : 'actualBoundingBoxDescent'
}

// Paints the share image onto `canvas` using `data` (the element's dataset -
// count, target, and the pre-localised t* strings baked in by leaf.blade.php).
export async function paint(canvas, data) {
  const target = data.target || 'Facebook'
  const dims = DIMENSIONS[target]

  if (!dims) {
    throw new Error('Unknown stats-share target: ' + target)
  }

  const portrait = isPortrait(target)
  const count = parseInt(data.count, 10) || 0

  canvas.width = dims.width
  canvas.height = dims.height

  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  const fill = (str, x, y, colour) => {
    ctx.fillStyle = colour || 'black'
    ctx.strokeStyle = colour || 'black'
    ctx.fillText(str, x, y)
    return x + ctx.measureText(str).width
  }

  const fillCentred = (text, y, wholeLine) => {
    const length = ctx.measureText(wholeLine || text).width
    // Centred on portrait images; left-aligned on landscape ones.
    const x = portrait ? (canvas.width - length) / 2 : canvas.width / 40
    return fill(text, x, y)
  }

  // Faithful port of fillWhiteBlackBox: unlike the lineHeight measurements below,
  // this does NOT fall back to actualBoundingBoxAscent/Descent when emHeightAscent/
  // Descent are unsupported (most current browsers), so `ctx.roundRect`/`ctx.rect`
  // get NaN coordinates there and silently draw nothing - only the white text itself
  // renders. That's what ships today; kept as-is rather than "fixed" so the box
  // renders identically (i.e. not at all) to the current production output.
  const fillBox = (str, x, y) => {
    const m = ctx.measureText(str)
    const boxX = x
    const boxY = y - m.emHeightAscent - MARG
    const boxWidth = m.width + MARG * 2
    const boxHeight = m.emHeightAscent + m.emHeightDescent + MARG * 2

    if (ctx.roundRect) {
      ctx.roundRect(boxX, boxY, boxWidth, boxHeight, RADIUS)
    } else {
      ctx.rect(boxX, boxY, boxWidth, boxHeight)
    }

    ctx.fill()
    // A fresh beginPath() stops future roundRect() calls extending this same path
    // and re-filling over what's already been written.
    ctx.beginPath()

    x += MARG
    x = fill(str, x, y, 'white')
    x += MARG
    return x
  }

  const bg = await loadImage('/images/stats/' + getImage(count, portrait))
  ctx.drawImage(bg, 0, 0, canvas.width, canvas.height)
  // Canvas compositing isn't guaranteed synchronous in every browser; the original
  // component waited before drawing text on top to avoid rendering artifacts.
  await wait(500)

  const fSize = fontSize(target, portrait)
  const baseX = portrait ? 0 : canvas.width / 20
  let x = baseX
  let y = target === 'Instagram' ? 100 : canvas.height / 5

  ctx.font = 'bold ' + fSize + 'px Asap, sans-serif'

  const co2Str = count.toLocaleString() + ' kg'
  const measure1 = ctx.measureText(data.tWeveSaved + co2Str + data.tOfCo2)
  const measure2 = ctx.measureText(co2Str)
  const lineHeight = measure1[ascKey(measure1)] + measure2[descKey(measure1)] + MARG * 2

  if (portrait) {
    const wholeline = data.tWeveSaved + co2Str + data.tOfCo2
    x = fillCentred(data.tWeveSaved + ' ', y, wholeline)
    x = fillBox(co2Str, x, y)
    x = fill(' ' + data.tOfCo2, x, y)

    y += lineHeight

    const wholeline2 = data.tByRepairing + ' ' + data.tBrokenStuff
    x = fillCentred(data.tByRepairing + ' ', y, wholeline2)
    x = fill(data.tBrokenStuff, x, y)
  } else {
    x = fill(data.tWeveSaved + ' ', x, y)
    x = fillBox(co2Str, x, y)

    y += lineHeight
    x = baseX

    x = fill(data.tOfCo2, x, y)
    x = fill(' ' + data.tByRepairing, x, y)

    y += lineHeight
    x = baseX

    x = fill(data.tBrokenStuff, x, y)
  }

  // Wavy divider line.
  if (portrait) {
    y += lineHeight / 2
  } else if (target === 'Twitter') {
    y = canvas.height / 2 - 39 + lineHeight / 2
  } else {
    y = canvas.height / 2 - 39 / 4
  }

  const dividerX = portrait ? (canvas.width - 292 / 2) / 2 : baseX
  const divider = await loadImage('/images/stats/WavyDividerLine.png')
  ctx.drawImage(divider, dividerX, y, 292 / 2, 39 / 2)
  await wait(500)

  y += portrait ? lineHeight + 39 / 4 : lineHeight + 39

  const numericCount = getCount(count)
  const unitStr = numericCount.toLocaleString()
  const smallFSize = smallerFontSize(target, portrait)

  ctx.font = 'bold ' + smallFSize + 'px Asap, sans-serif'

  // Reuses the "we've saved .. of CO2e" strings for this measurement, same as the
  // Vue original (not the thats-like/seedlings strings actually drawn below) -
  // it's only used to size the line spacing, so kept for parity.
  const measure3 = ctx.measureText(data.tWeveSaved + unitStr + data.tOfCo2)
  const measure4 = ctx.measureText(unitStr)
  const lineHeight2 = measure3[ascKey(measure3)] + measure4[descKey(measure3)] + MARG * 2

  const hectare = isHectareRange(count)
  const growingText = hectare ? data.tPlantingAround : data.tGrowingAbout
  const unitText = hectare
    ? pluralText(numericCount, data.tHectareSingular, data.tHectarePlural)
    : pluralText(numericCount, data.tSeedlingSingular, data.tSeedlingPlural)

  if (portrait) {
    const wholeline = data.tThatsLike + ' ' + growingText + ' '
    x = fillCentred(data.tThatsLike + ' ', y, wholeline)
    x = fill(growingText + ' ', x, y)
    x = fillBox(unitStr, x, y)

    y += lineHeight2
    x = baseX

    const wholeline2 = unitText
    x = fillCentred(unitText, y, wholeline2)
  } else {
    x = fill(data.tThatsLike, x, y)

    y += lineHeight2
    x = baseX

    x = fill(growingText + ' ', x, y)
    x = fillBox(unitStr, x, y)

    y += lineHeight2
    x = baseX

    x = fill(unitText, x, y)
  }
}

function readData(el) {
  return { ...el.dataset }
}

export function init(root = document) {
  root.querySelectorAll('[data-stats-share]').forEach((el) => {
    paint(el, readData(el)).catch((e) => {
      console.error('stats-share widget failed to paint', e)
    })
  })
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init())
  } else {
    init()
  }
}
