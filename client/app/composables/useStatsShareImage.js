// Port of resources/js/components/StatsShare.vue's pure lookup/sizing logic:
// the CO2e -> background-image lookup table (RANGES) and the per-platform
// canvas dimensions/font-size math. The canvas-drawing side (measuring and
// painting text) lives in components/events/StatsShareImage.vue, which
// calls into this composable - kept separate so the lookup table and sizing
// maths, which a screenshot diff can't reliably catch, are directly
// unit-testable without mounting a canvas.
//
// One row per background-image variant. `rangeIndex()` walks the table (in
// table order) to find the first row whose upperBoundary >= count, matching
// develop's rangeIndex() algorithm exactly (re-indexed from 1-based-with-
// header to plain 0-based, see below). The visualisation escalates as CO2e
// rises: single seedling (level 1) -> more seedlings (level 2) -> a square
// of seedlings, i.e. a small plantation (levels 3-4) -> a hectare of trees
// (levels 5-6).
const RANGES = [
  { level: 1, increment: 1, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 30, exactCo2: 60, upperBoundary: 89.99 },
  { level: 1, increment: 2, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 90, exactCo2: 120, upperBoundary: 149.99 },
  { level: 1, increment: 3, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 150, exactCo2: 180, upperBoundary: 209.99 },
  { level: 2, increment: 4, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 210, exactCo2: 240, upperBoundary: 269.99 },
  { level: 2, increment: 5, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 270, exactCo2: 300, upperBoundary: 329.99 },
  { level: 2, increment: 6, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 330, exactCo2: 360, upperBoundary: 389.99 },
  { level: 2, increment: 7, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 390, exactCo2: 420, upperBoundary: 449.99 },
  { level: 2, increment: 8, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 450, exactCo2: 480, upperBoundary: 509.99 },
  { level: 2, increment: 9, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 510, exactCo2: 540, upperBoundary: 569.99 },
  { level: 2, increment: 10, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 570, exactCo2: 600, upperBoundary: 629.99 },
  { level: 2, increment: 11, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 630, exactCo2: 660, upperBoundary: 689.99 },
  { level: 2, increment: 12, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 690, exactCo2: 720, upperBoundary: 749.99 },
  { level: 2, increment: 13, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 750, exactCo2: 780, upperBoundary: 809.99 },
  { level: 2, increment: 14, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 810, exactCo2: 840, upperBoundary: 869.99 },
  { level: 2, increment: 15, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 870, exactCo2: 900, upperBoundary: 929.99 },
  { level: 2, increment: 16, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 930, exactCo2: 960, upperBoundary: 989.99 },
  { level: 2, increment: 17, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 990, exactCo2: 1020, upperBoundary: 1049.99 },
  { level: 2, increment: 18, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1050, exactCo2: 1080, upperBoundary: 1109.99 },
  { level: 2, increment: 19, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1110, exactCo2: 1140, upperBoundary: 1169.99 },
  { level: 2, increment: 20, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1170, exactCo2: 1200, upperBoundary: 1229.99 },
  { level: 2, increment: 21, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1230, exactCo2: 1260, upperBoundary: 1289.99 },
  { level: 2, increment: 22, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1290, exactCo2: 1320, upperBoundary: 1349.99 },
  { level: 2, increment: 23, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1350, exactCo2: 1380, upperBoundary: 1409.99 },
  { level: 2, increment: 24, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1410, exactCo2: 1440, upperBoundary: 1469.99 },
  { level: 2, increment: 25, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1470, exactCo2: 1500, upperBoundary: 1529.99 },
  { level: 2, increment: 26, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1530, exactCo2: 1560, upperBoundary: 1589.99 },
  { level: 2, increment: 27, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1590, exactCo2: 1620, upperBoundary: 1649.99 },
  { level: 2, increment: 28, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1650, exactCo2: 1680, upperBoundary: 1709.99 },
  { level: 2, increment: 29, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1710, exactCo2: 1740, upperBoundary: 1769.99 },
  { level: 2, increment: 30, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1770, exactCo2: 1800, upperBoundary: 1829.99 },
  { level: 2, increment: 31, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1830, exactCo2: 1860, upperBoundary: 1889.99 },
  { level: 2, increment: 32, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1890, exactCo2: 1920, upperBoundary: 1949.99 },
  { level: 2, increment: 33, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 1950, exactCo2: 1980, upperBoundary: 2009.99 },
  { level: 2, increment: 34, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2010, exactCo2: 2040, upperBoundary: 2069.99 },
  { level: 2, increment: 35, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2070, exactCo2: 2100, upperBoundary: 2129.99 },
  { level: 2, increment: 36, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2130, exactCo2: 2160, upperBoundary: 2189.99 },
  { level: 2, increment: 37, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2190, exactCo2: 2220, upperBoundary: 2249.99 },
  { level: 2, increment: 38, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2250, exactCo2: 2280, upperBoundary: 2309.99 },
  { level: 2, increment: 39, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2310, exactCo2: 2340, upperBoundary: 2369.99 },
  { level: 2, increment: 40, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2370, exactCo2: 2400, upperBoundary: 2429.99 },
  { level: 2, increment: 41, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2430, exactCo2: 2460, upperBoundary: 2489.99 },
  { level: 2, increment: 42, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2490, exactCo2: 2520, upperBoundary: 2549.99 },
  { level: 2, increment: 43, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2550, exactCo2: 2580, upperBoundary: 2609.99 },
  { level: 2, increment: 44, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2610, exactCo2: 2640, upperBoundary: 2669.99 },
  { level: 2, increment: 45, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2670, exactCo2: 2700, upperBoundary: 2729.99 },
  { level: 2, increment: 46, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2730, exactCo2: 2760, upperBoundary: 2789.99 },
  { level: 2, increment: 47, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2790, exactCo2: 2820, upperBoundary: 2849.99 },
  { level: 2, increment: 48, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2850, exactCo2: 2880, upperBoundary: 2909.99 },
  { level: 2, increment: 49, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2910, exactCo2: 2940, upperBoundary: 2969.99 },
  { level: 2, increment: 50, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 2970, exactCo2: 3000, upperBoundary: 3029.99 },
  { level: 2, increment: 51, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3030, exactCo2: 3060, upperBoundary: 3089.99 },
  { level: 2, increment: 52, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3090, exactCo2: 3120, upperBoundary: 3149.99 },
  { level: 2, increment: 53, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3150, exactCo2: 3180, upperBoundary: 3209.99 },
  { level: 2, increment: 54, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3210, exactCo2: 3240, upperBoundary: 3269.99 },
  { level: 2, increment: 55, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3270, exactCo2: 3300, upperBoundary: 3329.99 },
  { level: 2, increment: 56, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3330, exactCo2: 3360, upperBoundary: 3389.99 },
  { level: 2, increment: 57, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3390, exactCo2: 3420, upperBoundary: 3449.99 },
  { level: 2, increment: 58, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3450, exactCo2: 3480, upperBoundary: 3509.99 },
  { level: 2, increment: 59, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3510, exactCo2: 3540, upperBoundary: 3569.99 },
  { level: 2, increment: 60, type: 'Seedling', co2PerIncrement: 60, lowerBoundary: 3570, exactCo2: 3600, upperBoundary: 3629.99 },
  { level: 3, increment: 2, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 3630, exactCo2: 3000, upperBoundary: 3749.99 },
  { level: 3, increment: 3, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 3750, exactCo2: 4500, upperBoundary: 5249.99 },
  { level: 3, increment: 4, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 5250, exactCo2: 6000, upperBoundary: 6749.99 },
  { level: 4, increment: 5, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 6750, exactCo2: 7500, upperBoundary: 8249.99 },
  { level: 4, increment: 6, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 8250, exactCo2: 9000, upperBoundary: 9749.99 },
  { level: 4, increment: 7, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 9750, exactCo2: 10500, upperBoundary: 11249.99 },
  { level: 4, increment: 8, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 11250, exactCo2: 12000, upperBoundary: 12749.99 },
  { level: 4, increment: 9, type: 'Square of seedlings', co2PerIncrement: 1500, lowerBoundary: 12750, exactCo2: 13500, upperBoundary: 14249.99 },
  { level: 5, increment: 1, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 14250, exactCo2: 12000, upperBoundary: 17999.99 },
  { level: 5, increment: 2, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 18000, exactCo2: 24000, upperBoundary: 29999.99 },
  { level: 5, increment: 3, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 30000, exactCo2: 36000, upperBoundary: 41999.99 },
  { level: 5, increment: 4, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 42000, exactCo2: 48000, upperBoundary: 53999.99 },
  { level: 5, increment: 5, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 54000, exactCo2: 60000, upperBoundary: 65999.99 },
  { level: 5, increment: 6, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 66000, exactCo2: 72000, upperBoundary: 77999.99 },
  { level: 5, increment: 7, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 78000, exactCo2: 84000, upperBoundary: 89999.99 },
  { level: 5, increment: 8, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 90000, exactCo2: 96000, upperBoundary: 101999.99 },
  { level: 5, increment: 9, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 102000, exactCo2: 108000, upperBoundary: 113999.99 },
  { level: 5, increment: 10, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 114000, exactCo2: 120000, upperBoundary: 125999.99 },
  { level: 5, increment: 11, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 126000, exactCo2: 132000, upperBoundary: 137999.99 },
  { level: 5, increment: 12, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 138000, exactCo2: 144000, upperBoundary: 149999.99 },
  { level: 5, increment: 13, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 150000, exactCo2: 156000, upperBoundary: 161999.99 },
  { level: 5, increment: 14, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 162000, exactCo2: 168000, upperBoundary: 173999.99 },
  { level: 5, increment: 15, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 174000, exactCo2: 180000, upperBoundary: 185999.99 },
  { level: 5, increment: 16, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 186000, exactCo2: 192000, upperBoundary: 191999.99 },
  { level: 6, increment: 16, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 192000, exactCo2: 192000, upperBoundary: 197999.99 },
  { level: 6, increment: 17, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 198000, exactCo2: 204000, upperBoundary: 209999.99 },
  { level: 6, increment: 18, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 210000, exactCo2: 216000, upperBoundary: 221999.99 },
  { level: 6, increment: 19, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 222000, exactCo2: 228000, upperBoundary: 233999.99 },
  { level: 6, increment: 20, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 234000, exactCo2: 240000, upperBoundary: 245999.99 },
  { level: 6, increment: 21, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 246000, exactCo2: 252000, upperBoundary: 257999.99 },
  { level: 6, increment: 22, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 258000, exactCo2: 264000, upperBoundary: 269999.99 },
  { level: 6, increment: 23, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 270000, exactCo2: 276000, upperBoundary: 281999.99 },
  { level: 6, increment: 24, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 282000, exactCo2: 288000, upperBoundary: 293999.99 },
  { level: 6, increment: 25, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 294000, exactCo2: 300000, upperBoundary: 305999.99 },
  { level: 6, increment: 26, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 306000, exactCo2: 312000, upperBoundary: 317999.99 },
  { level: 6, increment: 27, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 318000, exactCo2: 324000, upperBoundary: 329999.99 },
  { level: 6, increment: 28, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 330000, exactCo2: 336000, upperBoundary: 341999.99 },
  { level: 6, increment: 29, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 342000, exactCo2: 348000, upperBoundary: 353999.99 },
  { level: 6, increment: 30, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 354000, exactCo2: 360000, upperBoundary: 365999.99 },
  { level: 6, increment: 31, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 366000, exactCo2: 372000, upperBoundary: 377999.99 },
  { level: 6, increment: 32, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 378000, exactCo2: 384000, upperBoundary: 389999.99 },
  { level: 6, increment: 33, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 390000, exactCo2: 396000, upperBoundary: 401999.99 },
  { level: 6, increment: 34, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 402000, exactCo2: 408000, upperBoundary: 413999.99 },
  { level: 6, increment: 35, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 414000, exactCo2: 420000, upperBoundary: 425999.99 },
  { level: 6, increment: 36, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 426000, exactCo2: 432000, upperBoundary: 437999.99 },
  { level: 6, increment: 37, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 438000, exactCo2: 444000, upperBoundary: 449999.99 },
  { level: 6, increment: 38, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 450000, exactCo2: 456000, upperBoundary: 461999.99 },
  { level: 6, increment: 39, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 462000, exactCo2: 468000, upperBoundary: 473999.99 },
  { level: 6, increment: 40, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 474000, exactCo2: 480000, upperBoundary: 485999.99 },
  { level: 6, increment: 41, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 486000, exactCo2: 492000, upperBoundary: 497999.99 },
  { level: 6, increment: 42, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 498000, exactCo2: 504000, upperBoundary: 509999.99 },
  { level: 6, increment: 43, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 510000, exactCo2: 516000, upperBoundary: 521999.99 },
  { level: 6, increment: 44, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 522000, exactCo2: 528000, upperBoundary: 533999.99 },
  { level: 6, increment: 45, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 534000, exactCo2: 540000, upperBoundary: 545999.99 },
  { level: 6, increment: 46, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 546000, exactCo2: 552000, upperBoundary: 557999.99 },
  { level: 6, increment: 47, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 558000, exactCo2: 564000, upperBoundary: 569999.99 },
  { level: 6, increment: 48, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 570000, exactCo2: 576000, upperBoundary: 581999.99 },
  { level: 6, increment: 49, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 582000, exactCo2: 588000, upperBoundary: 593999.99 },
  { level: 6, increment: 50, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 594000, exactCo2: 600000, upperBoundary: 605999.99 },
  { level: 6, increment: 51, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 606000, exactCo2: 612000, upperBoundary: 617999.99 },
  { level: 6, increment: 52, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 618000, exactCo2: 624000, upperBoundary: 629999.99 },
  { level: 6, increment: 53, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 630000, exactCo2: 636000, upperBoundary: 641999.99 },
  { level: 6, increment: 54, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 642000, exactCo2: 648000, upperBoundary: 653999.99 },
  { level: 6, increment: 55, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 654000, exactCo2: 660000, upperBoundary: 665999.99 },
  { level: 6, increment: 56, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 666000, exactCo2: 672000, upperBoundary: 677999.99 },
  { level: 6, increment: 57, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 678000, exactCo2: 684000, upperBoundary: 689999.99 },
  { level: 6, increment: 58, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 690000, exactCo2: 696000, upperBoundary: 701999.99 },
  { level: 6, increment: 59, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 702000, exactCo2: 708000, upperBoundary: 713999.99 },
  { level: 6, increment: 60, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 714000, exactCo2: 720000, upperBoundary: 725999.99 },
  { level: 6, increment: 61, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 726000, exactCo2: 732000, upperBoundary: 737999.99 },
  { level: 6, increment: 62, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 738000, exactCo2: 744000, upperBoundary: 749999.99 },
  { level: 6, increment: 63, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 750000, exactCo2: 756000, upperBoundary: 761999.99 },
  { level: 6, increment: 64, type: 'Hectare', co2PerIncrement: 12000, lowerBoundary: 762000, exactCo2: 768000, upperBoundary: 774000 },
]

export const STATS_SHARE_PLATFORMS = ['Instagram', 'Facebook', 'Twitter', 'LinkedIn']

// StatsShare.vue's width/height computeds - one canvas size per platform.
const DIMENSIONS = {
  Instagram: { width: 1080, height: 1080 },
  Facebook: { width: 1200, height: 630 },
  Twitter: { width: 1600, height: 900 },
  LinkedIn: { width: 1200, height: 627 },
}

export function useStatsShareImage() {
  // develop's rangeIndex() scans a header-plus-133-rows array starting at
  // index 1 (skipping the header), so its result is a 1-based index into
  // that array - equivalent to a plain 0-based index into RANGES here.
  // Unlike develop, this clamps to the last row instead of walking past the
  // end of the array: develop's loop bound (`ix < RANGES.length`, header
  // included) lets ix reach one past the last valid row for CO2e totals
  // above 774 tonnes, which would then dereference `undefined` and throw -
  // a latent crash bug in production for a case so large it's never been
  // hit, fixed here defensively rather than reproduced.
  function rangeIndex(count) {
    const parsed = parseInt(count, 10) || 0
    let ix = 0
    while (ix < RANGES.length - 1 && parsed > RANGES[ix].upperBoundary) {
      ix++
    }
    return ix
  }

  function rangeForCount(count) {
    return RANGES[rangeIndex(count)]
  }

  function isPortrait(target) {
    return target === 'Instagram'
  }

  // The numeric value shown in "growing about X seedlings" / "planting
  // around X hectares" - for the 'Square of seedlings' visualisation this is
  // the seedling count (count / 60), not the square count, matching
  // StatsShare.vue's getCount().
  function getCount(count) {
    const range = rangeForCount(count)
    if (range.type === 'Square of seedlings') {
      return Math.round(parseInt(count, 10) / 60)
    }
    return range.increment
  }

  // StatsShare.vue's getImage(): background filename for a given CO2e total
  // and platform, e.g. 'ImpactRange2Landscape-37.png'.
  function getImage(count, target) {
    const range = rangeForCount(count)
    const orientation = isPortrait(target) ? 'Square' : 'Landscape'
    return `ImpactRange${range.level}${orientation}-${range.increment}.png`
  }

  function dimensions(target) {
    return DIMENSIONS[target] || null
  }

  // StatsShare.vue's initialY computed.
  function initialY(target) {
    if (target === 'Instagram') return 100
    const size = dimensions(target)
    return size ? size.height / 5 : 0
  }

  // StatsShare.vue's initialX computed.
  function initialX(target) {
    if (isPortrait(target)) return 0
    const size = dimensions(target)
    return size ? size.width / 20 : 0
  }

  // StatsShare.vue's fontSize computed() is a switch statement with no
  // `break` per case, so JS falls through every case to the LAST one in the
  // block (Instagram, then Facebook, then Twitter, then LinkedIn) - the
  // value that's actually assigned is always LinkedIn's, whichever target
  // was selected. Since only Instagram is ever portrait, and only Facebook/
  // Twitter/LinkedIn are ever landscape, what actually renders in
  // production today is:
  //   portrait (Instagram only): base 40 (the portrait LinkedIn case)
  //   landscape (any of the 3):  base 45 (the landscape LinkedIn case)
  // - the per-platform values the switch cases assign but can never reach
  // (Instagram 55, Facebook 40, Twitter 52 portrait; Instagram 110,
  // Facebook 50, Twitter 65 landscape) are dead code in production.
  // Reproduced verbatim below (including the locale multipliers, applied on
  // top of those fallen-through bases) for visual parity with develop.
  function fontSize(target, locale) {
    const isFrench = locale === 'fr' || locale === 'fr-BE'
    const isEnglish = locale === 'en'

    if (isPortrait(target)) {
      const base = 40
      // Both of develop's fr/fr-BE and en branches apply the same *7/6
      // multiplier, so every supported locale ends up identical: 47.
      return isFrench || isEnglish ? Math.round((base * 7) / 6) : base
    }

    const base = 45
    if (target === 'Twitter') {
      if (isFrench) return Math.round((base * 7) / 6)
      if (isEnglish) return Math.round((base * 4) / 3)
      return base
    }
    return isFrench ? Math.round((base * 6) / 7) : base
  }

  function smallerFontSize(target, locale) {
    return Math.round((fontSize(target, locale) * 4) / 5)
  }

  return {
    rangeIndex,
    rangeForCount,
    isPortrait,
    getCount,
    getImage,
    dimensions,
    initialX,
    initialY,
    fontSize,
    smallerFontSize,
  }
}
