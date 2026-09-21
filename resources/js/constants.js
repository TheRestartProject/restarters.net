// User roles.
export const ROOT = 1
export const ADMINISTRATOR = 2
export const HOST = 3
export const RESTARTER = 4
export const GUEST = 5
export const NETWORK_COORDINATOR = 6

export const DEFAULT_PROFILE = '/images/placeholder-avatar.png'
export const PLACEHOLDER = '/images/placeholder.png'
export const DATE_FORMAT = 'ddd Do MMM Y'
export const TIME_FORMAT = 'HH:MM'

export const LEAFLET_TILES = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png'
export const LEAFLET_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attribution">CARTO</a>'

// CARTO's raster basemaps require an API key; without one the tiles still load
// but carry an "API key required" watermark. The key is injected into the page
// by the Blade layout (see layouts/header.blade.php) rather than compiled in,
// because the assets are built before the deployed environment's secrets exist.
export function leafletTiles() {
  const key = (typeof window !== 'undefined' && window.restarters && window.restarters.cartoApiKey) || ''

  return key ? `${LEAFLET_TILES}?key=${encodeURIComponent(key)}` : LEAFLET_TILES
}

// These match the string definitions in Device.php
export const FIXED = 'Fixed'
export const REPAIRABLE = 'Repairable'
export const END_OF_LIFE = 'End of life'

// These match the string definitions in Device.php
export const NEXT_STEPS_MORE_TIME = 'More time needed'
export const NEXT_STEPS_PROFESSIONAL = 'Professional help'
export const NEXT_STEPS_DIY = 'Do it yourself'

// These match the string definitions in Device.php
export const SPARE_PARTS_MANUFACTURER = 'Manufacturer'
export const SPARE_PARTS_NOT_NEEDED = 'No'
export const SPARE_PARTS_THIRD_PARTY = 'Third party'
export const SPARE_PARTS_HISTORICAL = 4

export const PARTS_PROVIDER_MANUFACTURER = 1
export const PARTS_PROVIDER_THIRD_PARTY = 2

export const USEFUL_URL_SOURCE_MANUFACTURER = 1
export const USEFUL_URL_SOURCE_THIRD_PARTY = 2

export const CATEGORY_MISC_POWERED = 46
export const CATEGORY_MISC_UNPOWERED = 50

export const UNKNOWN_STRINGS = [
    'unknown',
    'n/a',
    'not applicable',
    '?',
    'not known',
    'don\'t know',
    'unbranded',
    'no brand',
    'no model',
    'none'
]

export const MIN_MAP_ZOOM = 1
// 14 was too shallow to read street names or tell close-together pins apart
// (user feedback on the groups map); CARTO's raster tiles serve up to z18.
export const MAX_MAP_ZOOM = 18